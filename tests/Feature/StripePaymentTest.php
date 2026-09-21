<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Exceptions\StripeException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\StripeService;
use Illuminate\Support\Facades\Config;
use Stripe\Checkout\Session;

function fakeStripeClient(?Session $session = null): object
{
    $created = [];

    return new class($session, $created)
    {
        public object $checkout;

        public function __construct(?Session $session, array &$created)
        {
            $this->checkout = new class($session, $created)
            {
                public object $sessions;

                public function __construct(?Session $session, array &$created)
                {
                    $this->sessions = new class($session, $created)
                    {
                        public function __construct(private ?Session $session, private array &$created) {}

                        public function create(array $params): Session
                        {
                            $this->created[] = $params;

                            return Session::constructFrom([
                                'id' => 'cs_test_fake123',
                                'url' => 'https://checkout.stripe.com/pay/cs_test_fake123',
                                'payment_status' => 'unpaid',
                                'amount_total' => $params['line_items'][0]['price_data']['unit_amount'],
                                'metadata' => $params['metadata'],
                            ]);
                        }

                        public function retrieve(string $id): Session
                        {
                            return $this->session ?? throw new Exception("Unknown session {$id}");
                        }

                        public function createdParams(): array
                        {
                            return $this->created;
                        }
                    };
                }
            };
        }
    };
}

function paidSession(Invoice $invoice, ?float $amount = null): Session
{
    $invoice->refresh();

    return Session::constructFrom([
        'id' => 'cs_test_paid456',
        'payment_status' => 'paid',
        'amount_total' => (int) round(($amount ?? (float) $invoice->total) * 100),
        'metadata' => ['invoice_id' => $invoice->id],
    ]);
}

function stripeService(?object $client = null): StripeService
{
    return new StripeService(app(PaymentService::class), $client ?? fakeStripeClient());
}

it('creates a checkout session for the remaining balance', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);
    $customer = $invoice->user;

    $this->app->bind(StripeService::class, fn ($app) => stripeService());

    $this->actingAs($customer)->post(route('invoices.stripe.checkout', $invoice))
        ->assertRedirect('https://checkout.stripe.com/pay/cs_test_fake123');

    $stranger = User::factory()->create(['role' => UserRole::Customer]);
    $this->actingAs($stranger)->post(route('invoices.stripe.checkout', $invoice))->assertNotFound();
});

it('routes card payments through Stripe and records cash directly', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);
    $customer = $invoice->user;

    $this->app->bind(StripeService::class, fn ($app) => stripeService());

    // Card → Stripe Checkout (partial amount honored).
    $this->actingAs($customer)->post(route('invoices.pay', $invoice), [
        'amount' => 100.00,
        'method' => 'card',
    ])->assertRedirect('https://checkout.stripe.com/pay/cs_test_fake123');

    expect(Payment::count())->toBe(0);

    // Cash → recorded immediately.
    $this->actingAs($customer)->post(route('invoices.pay', $invoice), [
        'amount' => 100.00,
        'method' => 'cash',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::PartiallyPaid);
});

it('settles paid sessions idempotently and refuses the rest', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);
    $customer = $invoice->user;
    $service = stripeService(fakeStripeClient(paidSession($invoice)));

    $first = $service->handleSuccess('cs_test_paid456', $customer);
    $second = $service->handleSuccess('cs_test_paid456', $customer);

    expect($first->id)->toBe($second->id)
        ->and(Payment::where('reference', 'cs_test_paid456')->count())->toBe(1)
        ->and($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);

    // Unpaid session.
    $unpaid = Session::constructFrom([
        'id' => 'cs_test_unpaid',
        'payment_status' => 'unpaid',
        'amount_total' => 10000,
        'metadata' => ['invoice_id' => $invoice->id],
    ]);
    expect(fn () => stripeService(fakeStripeClient($unpaid))->handleSuccess('cs_test_unpaid', $customer))
        ->toThrow(StripeException::class);

    // Foreign invoice.
    $other = Invoice::factory()->create();
    $foreign = Session::constructFrom([
        'id' => 'cs_test_foreign',
        'payment_status' => 'paid',
        'amount_total' => 10000,
        'metadata' => ['invoice_id' => $other->id],
    ]);
    expect(fn () => stripeService(fakeStripeClient($foreign))->handleSuccess('cs_test_foreign', $customer))
        ->toThrow(StripeException::class);
});

it('requires stripe configuration without an injected client', function () {
    Config::set('services.stripe.secret', null);

    $invoice = Invoice::factory()->create();
    $service = new StripeService(app(PaymentService::class));

    expect(fn () => $service->handleSuccess('x', $invoice->user))
        ->toThrow(StripeException::class);
});
