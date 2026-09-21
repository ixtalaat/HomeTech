<?php

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AdditionalWorkService;
use App\Services\CancellationService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\WorkOrderService;

it('writes an audit entry for every sensitive action (BR-012)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    // Discount.
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->applyDiscount($invoice, $manager, DiscountType::Fixed, 10.00);
    expect(AuditLog::where('action', 'invoice.discounted')->count())->toBe(1);

    // Payment.
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);
    app(PaymentService::class)->pay($invoice->refresh(), 50.00, PaymentMethod::Cash, $admin);
    expect(AuditLog::where('action', 'payment.received')->count())->toBe(1);

    // Inventory adjustment.
    $item = InventoryItem::factory()->create(['current_stock' => 10]);
    app(InventoryService::class)->adjust($item, -2, $admin, 'Damaged.');
    expect(AuditLog::where('action', 'inventory.adjusted')->where('actor_id', $admin->id)->count())->toBe(1);

    // Completed-job correction.
    $completed = WorkOrder::factory()->completed()->create();
    app(WorkOrderService::class)->correct($completed, $admin, ['diagnosis' => 'Fixed.'], 'Typo.');
    expect(AuditLog::where('action', 'work_order.corrected')->count())->toBe(1);

    // Additional-work decision.
    $open = inProgressWorkOrder();
    $decidedBefore = AuditLog::where('action', 'additional_work.decided')->count();
    $extra = app(AdditionalWorkService::class)->request($open, $open->technician->user, 'Extra.', 25.00);
    app(AdditionalWorkService::class)->decide($extra->refresh(), $open->request->user, true);
    expect(AuditLog::where('action', 'additional_work.decided')->count())->toBe($decidedBefore + 1);

    // Cancellation with fee context.
    $request = scheduledRequest();
    app(CancellationService::class)->cancel($request->refresh(), $request->user, 'No longer needed.');
    $cancelLog = AuditLog::where('action', 'request.cancelled')->first();
    expect($cancelLog)->not->toBeNull()
        ->and($cancelLog->reason)->toBe('No longer needed.');
});
