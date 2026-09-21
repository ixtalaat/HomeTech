<?php

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentChanged;
use App\Notifications\InvoiceIssued;
use App\Notifications\JobAssigned;
use App\Notifications\JobCancelled;
use App\Notifications\JobCompleted;
use App\Notifications\JobRescheduled;
use App\Notifications\PaymentReceived;
use App\Notifications\RequestApproved;
use App\Notifications\RequestSubmitted;
use App\Notifications\TechnicianAssignedToRequest;
use App\Services\CancellationService;
use App\Services\InvoiceService;
use App\Services\MaintenanceRequestService;
use App\Services\PaymentService;
use App\Services\RequestReviewService;
use App\Services\SchedulingService;
use App\Services\TechnicianAssignmentService;
use App\Services\WorkOrderService;
use Illuminate\Support\Facades\Notification;

it('fires the correct notification per lifecycle event', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $service = Service::factory()->create(['is_active' => true, 'estimated_duration_minutes' => 60]);
    $address = Address::factory()->create(['user_id' => $customer->id]);

    // Submit.
    $request = app(MaintenanceRequestService::class)->create($customer, [
        'service_id' => $service->id,
        'address_id' => $address->id,
        'description' => 'AC not cooling.',
        'preferred_date' => now()->addDays(3)->format('Y-m-d'),
        'preferred_time' => '10:00',
    ]);
    Notification::assertSentTo($customer, RequestSubmitted::class);

    // Approve.
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    app(RequestReviewService::class)->approve($request->refresh(), $admin, []);
    Notification::assertSentTo($customer, RequestApproved::class);

    // Assign (customer + technician).
    $technician = skilledTechnician($request->service);
    app(TechnicianAssignmentService::class)->assign($request->refresh(), $technician, $admin);
    Notification::assertSentTo($customer, TechnicianAssignedToRequest::class);
    Notification::assertSentTo($technician->user, JobAssigned::class);

    // Reschedule (customer + technician).
    $scheduling = app(SchedulingService::class);
    $scheduling->reschedule(
        $request->appointment,
        now()->addDays(6)->format('Y-m-d'),
        '14:00',
        '16:00',
        $admin
    );
    Notification::assertSentTo($customer, AppointmentChanged::class);
    Notification::assertSentTo($technician->user, JobRescheduled::class);

    // Complete the visit (diagnosis + notes first).
    $workOrders = app(WorkOrderService::class);
    $workOrder = $workOrders->startVisit($request->refresh(), $technician->user);
    $workOrders->recordDiagnosis($workOrder, $technician->user, 'Faulty capacitor.');
    $workOrders->recordNotes($workOrder, $technician->user, 'Replaced and tested.');
    $workOrders->complete($workOrder->refresh(), $technician->user);
    Notification::assertSentTo($customer, JobCompleted::class);

    // Invoice + payment.
    $invoices = app(InvoiceService::class);
    $invoice = $invoices->generate($workOrder->refresh(), $admin);
    $invoices->issue($invoice, $admin);
    Notification::assertSentTo($customer, InvoiceIssued::class);

    app(PaymentService::class)->pay($invoice->refresh(), (float) $invoice->total, PaymentMethod::Cash, $admin);
    Notification::assertSentTo($customer, PaymentReceived::class);
});

it('notifies the technician on cancellation', function () {
    Notification::fake();

    $request = scheduledRequest();
    $technicianUser = $request->technician->user;

    app(CancellationService::class)->cancel($request->refresh(), $request->user, 'Changed mind.');

    Notification::assertSentTo($technicianUser, JobCancelled::class);
});

it('shows the inbox with unread markers and marks read on open', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->notify(new RequestSubmitted(MaintenanceRequest::factory()->create(['user_id' => $customer->id])));

    $this->actingAs($customer)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('pending review');

    expect($customer->refresh()->unreadNotifications()->count())->toBe(0);
});
