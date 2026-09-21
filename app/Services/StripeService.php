<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\BillingException;
use App\Exceptions\StripeException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    /**
     * Accepts the real SDK client or a test double with the same shape.
     */
    public function __construct(
        private PaymentService $payments,
        private ?object $client = null
    ) {}

    /**
     * Create a hosted Checkout Session for full or partial payment.
     *
     * Amounts go to Stripe in piasters (EGP × 100).
     *
     * @throws StripeException
     */
    public function checkout(Invoice $invoice, User $customer, ?float $amount = null): Session
    {
        if (! $invoice->isOwnedBy($customer)) {
            throw new StripeException('You can only pay your own invoices online.');
        }

        if (! $invoice->acceptsPayments()) {
            throw new StripeException("Invoice {$invoice->number} cannot be paid online in status '{$invoice->status->value}'.");
        }

        $amount = $amount === null ? $invoice->remaining() : round($amount, 2);

        if ($amount <= 0 || $amount > $invoice->remaining()) {
            throw new StripeException("Online payment must be between 1 piaster and {$invoice->remaining()} EGP.");
        }

        $piaster = (int) round($amount * 100);

        try {
            return $this->client()->checkout->sessions->create([
                'mode' => 'payment',
                'currency' => 'egp',
                'customer_email' => $customer->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'egp',
                        'product_data' => ['name' => "HomeTech invoice {$invoice->number}"],
                        'unit_amount' => $piaster,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => ['invoice_id' => $invoice->id],
                'success_url' => route('invoices.stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('invoices.stripe.cancel', $invoice),
            ]);
        } catch (ApiErrorException $exception) {
            throw new StripeException('Could not start the online payment: '.$exception->getMessage());
        }
    }

    /**
     * Settle a completed Checkout Session, idempotently.
     *
     * Safe to retry: an already-recorded session returns its payment.
     *
     * @throws StripeException
     */
    public function handleSuccess(string $sessionId, User $actor): Payment
    {
        try {
            $session = $this->client()->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException $exception) {
            throw new StripeException('Could not verify the online payment: '.$exception->getMessage());
        }

        if (($session->payment_status ?? null) !== 'paid') {
            throw new StripeException('This Stripe session is not paid yet.');
        }

        $invoiceId = (int) ($session->metadata->invoice_id ?? 0);
        $invoice = Invoice::find($invoiceId);

        if ($invoice === null || ! $invoice->isOwnedBy($actor)) {
            throw new StripeException('This payment session does not match your invoices.');
        }

        $existing = Payment::where('reference', $session->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($session, $invoice, $actor): Payment {
            try {
                return $this->payments->pay(
                    $invoice->refresh(),
                    round(((float) ($session->amount_total ?? 0)) / 100, 2),
                    PaymentMethod::Card,
                    $actor,
                    $session->id,
                    true
                );
            } catch (BillingException $exception) {
                throw new StripeException($exception->getMessage());
            }
        });
    }

    /**
     * Get the configured SDK client.
     *
     * @throws StripeException
     */
    private function client(): object
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new StripeException('Online payments are not configured (STRIPE_SECRET missing).');
        }

        return new StripeClient($secret);
    }
}
