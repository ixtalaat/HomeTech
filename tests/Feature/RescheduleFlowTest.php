<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\RequestApproved;
use App\Notifications\RescheduleNeeded;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

function unassignedApprovedRequest(?Service $service = null, string $city = 'Riyadh'): MaintenanceRequest
{
    $request = mondayRequest($service ?? hourlyService(), $city);
    $request->forceFill(['status' => RequestStatus::Approved, 'technician_id' => null])->save();

    return $request->refresh();
}

it('notifies the customer to reschedule when approval finds nobody', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = hourlyService();
    $branch = staffedBranch('Riyadh');
    $technician = workingTechnician($service, $branch);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    Appointment::factory()->create(['technician_id' => $technician->id, 'date' => $date, 'start_time' => '08:00', 'end_time' => '09:00']);
    Appointment::factory()->create(['technician_id' => $technician->id, 'date' => $date, 'start_time' => '09:00', 'end_time' => '10:00']);

    $request = mondayRequest($service, 'Riyadh');
    $request->update(['status' => RequestStatus::PendingReview]);
    $customer = $request->user;

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request), [
        'admin_note' => 'Looks good.',
    ])->assertRedirect();

    Notification::assertSentTo($customer, RequestApproved::class);
    Notification::assertSentTo($customer, RescheduleNeeded::class);
});

it('does not ask for reschedule when the city has no branch', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = mondayRequest(hourlyService(), 'Nowhere');
    $request->update(['status' => RequestStatus::PendingReview]);
    $customer = $request->user;

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request))->assertRedirect();

    Notification::assertSentTo($customer, RequestApproved::class);
    expect(Notification::sent($customer, RescheduleNeeded::class))->toBeEmpty();
});

it('lets customers reschedule and auto-assigns the new slot', function () {
    $service = hourlyService();
    $branch = staffedBranch('Riyadh');
    $technician = workingTechnician($service, $branch);
    $request = unassignedApprovedRequest($service);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    $this->actingAs($request->user)->patch(route('requests.reschedule', $request), [
        'preferred_date' => $date,
        'preferred_time' => '14:00',
    ])->assertRedirect(route('requests.show', $request))
        ->assertSessionHas('success');

    expect($request->refresh()->technician_id)->toBe($technician->id)
        ->and($request->status)->toBe(RequestStatus::Scheduled);
});

it('keeps the new slot with an error when rescheduling still fails', function () {
    $service = hourlyService();
    $branch = staffedBranch('Riyadh');
    $technician = workingTechnician($service, $branch);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    Appointment::factory()->create(['technician_id' => $technician->id, 'date' => $date, 'start_time' => '13:30', 'end_time' => '14:30']);

    $request = unassignedApprovedRequest($service);

    $this->actingAs($request->user)->patch(route('requests.reschedule', $request), [
        'preferred_date' => $date,
        'preferred_time' => '14:00',
    ])->assertRedirect(route('requests.show', $request))
        ->assertSessionHas('success')
        ->assertSessionHas('error');

    expect($request->refresh()->preferred_time)->toBe('14:00:00')
        ->and($request->technician_id)->toBeNull()
        ->and($request->status)->toBe(RequestStatus::Approved);
});

it('forbids rescheduling foreign or assigned requests', function () {
    $request = unassignedApprovedRequest();
    $other = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($other)->patch(route('requests.reschedule', $request), [
        'preferred_date' => Carbon::parse('next monday')->format('Y-m-d'),
        'preferred_time' => '14:00',
    ])->assertNotFound();

    $request->update(['status' => RequestStatus::Scheduled, 'technician_id' => workingTechnician(hourlyService(), staffedBranch('Jeddah'))->id]);

    $this->actingAs($request->user)->patch(route('requests.reschedule', $request), [
        'preferred_date' => Carbon::parse('next monday')->format('Y-m-d'),
        'preferred_time' => '14:00',
    ])->assertForbidden();
});

it('validates the new slot is in the future', function () {
    $request = unassignedApprovedRequest();

    $this->actingAs($request->user)->patch(route('requests.reschedule', $request), [
        'preferred_date' => now()->subDay()->format('Y-m-d'),
        'preferred_time' => '14:00',
    ])->assertSessionHasErrors('preferred_date');
});

it('renders the reschedule notice content', function () {
    $request = unassignedApprovedRequest();
    $customer = $request->user;

    $customer->notify(new RescheduleNeeded($request, '1 at the daily limit of 2'));

    $stored = $customer->notifications()->firstOrFail()->data;

    expect($stored['maintenance_request_id'])->toBe($request->id)
        ->and($stored['message'])->toContain('1 at the daily limit of 2');
});
