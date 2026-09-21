<?php

use App\Enums\DiscountType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\AdditionalWork;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PricingService;

it('calculates the pricing breakdown with tracked components', function () {
    $workOrder = completedWorkOrderWithCharges();
    $breakdown = app(PricingService::class)->breakdown($workOrder->refresh());

    expect($breakdown['labor'])->toBe(100.00)
        ->and($breakdown['materials'])->toBe(150.00)
        ->and($breakdown['additional'])->toBe(100.00)
        ->and($breakdown['subtotal'])->toBe(round($breakdown['service_base'] + 350.00, 2));
});

it('applies fixed and percent discounts with guards', function () {
    $pricing = app(PricingService::class);
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($pricing->discountAmount(1000.00, DiscountType::Fixed, 50.00, $admin))->toBe(50.00);
    expect($pricing->discountAmount(1000.00, DiscountType::Percent, 10.00, $admin))->toBe(100.00);

    // Negative total refused.
    expect(fn () => $pricing->discountAmount(100.00, DiscountType::Fixed, 150.00, $manager))
        ->toThrow(BillingException::class);

    // Percent over 100 refused.
    expect(fn () => $pricing->discountAmount(100.00, DiscountType::Percent, 150.00, $manager))
        ->toThrow(BillingException::class);

    // High-value discount needs a manager.
    expect(fn () => $pricing->discountAmount(1000.00, DiscountType::Percent, 25.00, $admin))
        ->toThrow(BillingException::class);
    expect($pricing->discountAmount(1000.00, DiscountType::Percent, 25.00, $manager))->toBe(250.00);

    expect(fn () => $pricing->discountAmount(1000.00, DiscountType::Fixed, 600.00, $admin))
        ->toThrow(BillingException::class);
});

it('generates draft invoices with valid source lines only (BR-010)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();

    // Rejected extras must never appear on the invoice (BR-010).
    AdditionalWork::factory()->rejected()->create(['work_order_id' => $workOrder->id]);

    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->number)->toStartWith('INV-')
        ->and($invoice->items)->toHaveCount(4);

    $types = $invoice->items->pluck('item_type')->all();
    expect($types)->toContain(InvoiceItemType::Service, InvoiceItemType::Labor, InvoiceItemType::Material, InvoiceItemType::Additional);

    foreach ($invoice->items as $line) {
        expect($line->source)->not->toBeNull();
    }

    expect((float) $invoice->total)->toBe((float) $invoice->subtotal);

    // Duplicate generation refused.
    expect(fn () => app(InvoiceService::class)->generate($workOrder->refresh(), $admin))
        ->toThrow(BillingException::class);
});

it('issues invoices and moves the request to invoiced', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);

    // Drafts cannot take payments.
    expect(fn () => app(PaymentService::class)->pay($invoice, 10.00, PaymentMethod::Cash, $admin))
        ->toThrow(BillingException::class);

    app(InvoiceService::class)->issue($invoice->refresh(), $admin);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->request->refresh()->status)->toBe(RequestStatus::Invoiced);
});

it('records partial and full payments with automatic status (BR-006)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);

    $total = (float) $invoice->refresh()->total;

    $this->actingAs($customer)->post(route('invoices.pay', $invoice), [
        'amount' => 100.00,
        'method' => 'cash',
    ])->assertForbidden();

    app(PaymentService::class)->pay($invoice->refresh(), 100.00, PaymentMethod::Cash, $admin);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::PartiallyPaid);

    // Over-payment refused, quoting the remainder.
    expect(fn () => app(PaymentService::class)->pay($invoice->refresh(), $total, PaymentMethod::Card, $admin))
        ->toThrow(BillingException::class);

    app(PaymentService::class)->pay($invoice->refresh(), $total - 100.00, PaymentMethod::Card, $admin);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->request->refresh()->status)->toBe(RequestStatus::Paid);

    // Paid invoices refuse more money.
    expect(fn () => app(PaymentService::class)->pay($invoice->refresh(), 1.00, PaymentMethod::Cash, $admin))
        ->toThrow(BillingException::class);
});

it('lets owners pay their own invoices', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = completedWorkOrderWithCharges();
    $customer = $workOrder->request->user;
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);

    $this->actingAs($customer)->post(route('invoices.pay', $invoice), [
        'amount' => (float) $invoice->refresh()->total,
        'method' => 'card',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
});

it('closes paid invoices and cancels unpaid ones', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = app(InvoiceService::class);

    $paid = Invoice::factory()->paid()->create();
    $paid->request->update(['status' => RequestStatus::Paid]);
    $service->markClosed($paid, $admin);
    expect($paid->request->refresh()->status)->toBe(RequestStatus::Closed);

    $draft = Invoice::factory()->draft()->create();
    $service->cancelInvoice($draft, $admin);
    expect($draft->refresh()->status)->toBe(InvoiceStatus::Cancelled);

    // Paid invoices cannot be cancelled or discounted.
    expect(fn () => $service->cancelInvoice($paid->refresh(), $admin))->toThrow(BillingException::class);
    expect(fn () => $service->applyDiscount($paid->refresh(), User::factory()->create(['role' => UserRole::Manager]), DiscountType::Fixed, 10.00))
        ->toThrow(BillingException::class);
});

it('forbids non-staff from the billing admin area', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $invoice = Invoice::factory()->create();

    $this->actingAs($customer)->get(route('admin.invoices.index'))->assertForbidden();
    $this->actingAs($customer)->patch(route('admin.invoices.issue', $invoice))->assertForbidden();
});
