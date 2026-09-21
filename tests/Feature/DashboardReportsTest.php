<?php

use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReportingService;

it('loads the dashboard and all four reports for staff', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice, $admin);
    app(PaymentService::class)->pay($invoice->refresh(), (float) $invoice->total, PaymentMethod::Cash, $admin);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Operations Dashboard');
    $this->actingAs($admin)->get(route('admin.reports.revenue'))->assertOk()
        ->assertSee(number_format($invoice->total, 2))
        ->assertSee($workOrder->technician->user->name);
    $this->actingAs($admin)->get(route('admin.reports.jobs'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reports.technicians'))->assertOk()->assertSee($workOrder->technician->user->name);
    $this->actingAs($admin)->get(route('admin.reports.inventory'))->assertOk();

    $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.reports.revenue'))->assertForbidden();
});

it('aggregates revenue, jobs, technicians, and inventory correctly', function () {
    $reporting = app(ReportingService::class);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice, $admin);
    app(PaymentService::class)->pay($invoice->refresh(), (float) $invoice->total, PaymentMethod::Cash, $admin);

    expect($reporting->totalRevenue())->toBe((float) $invoice->total);
    expect($reporting->outstandingInvoices())->toBeEmpty();

    $byStatus = $reporting->jobsByStatus();
    expect($byStatus[RequestStatus::Paid->value] ?? 0)->toBeGreaterThanOrEqual(1);

    expect($reporting->averageCompletionHours())->not->toBeNull();

    $techRow = $reporting->technicianStats()->firstWhere('id', $workOrder->technician_id);
    expect((float) $techRow->revenue)->toBe((float) $invoice->total);

    expect($reporting->revenueByService()->sum('total'))->toBeGreaterThan(0);
    expect($reporting->inventoryReport())->toHaveKeys(['items', 'low_stock', 'most_used', 'recent_movements']);
});
