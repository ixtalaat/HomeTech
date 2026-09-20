<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\User;

it('forbids non-staff from reviewing requests', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $request = MaintenanceRequest::factory()->create();

    $this->actingAs($user)->get(route('admin.requests.index'))->assertForbidden();
    $this->actingAs($user)->patch(route('admin.requests.approve', $request))->assertForbidden();
    $this->actingAs($user)->patch(route('admin.requests.reject', $request), ['rejection_reason' => 'No'])->assertForbidden();
})->with([
    'customer' => UserRole::Customer,
    'technician' => UserRole::Technician,
]);

it('approves a pending request and records history', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request), [
        'admin_note' => 'Looks good.',
    ])->assertRedirect(route('admin.requests.show', $request));

    expect($request->refresh()->status)->toBe(RequestStatus::Approved)
        ->and($request->admin_note)->toBe('Looks good.')
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->statusHistories->last()->status)->toBe(RequestStatus::Approved->value);
});

it('rejects a pending request with a required reason', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);

    $this->actingAs($admin)->patch(route('admin.requests.reject', $request))
        ->assertSessionHasErrors('rejection_reason');

    $this->actingAs($admin)->patch(route('admin.requests.reject', $request), [
        'rejection_reason' => 'Outside service area.',
    ])->assertRedirect(route('admin.requests.show', $request));

    expect($request->refresh()->status)->toBe(RequestStatus::Rejected)
        ->and($request->rejection_reason)->toBe('Outside service area.');
});

it('requests more info and allows resubmission to pending review', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);

    $this->actingAs($admin)->patch(route('admin.requests.request-info', $request), [
        'admin_note' => 'Please add photos of the unit.',
    ])->assertRedirect();

    expect($request->refresh()->status)->toBe(RequestStatus::InfoRequested);
});

it('changes the appointment during review', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);
    $newDate = now()->addDays(7)->format('Y-m-d');

    $this->actingAs($admin)->patch(route('admin.requests.appointment', $request), [
        'preferred_date' => $newDate,
        'preferred_time' => '14:30',
    ])->assertRedirect();

    expect($request->refresh()->preferred_date->format('Y-m-d'))->toBe($newDate);
});

it('rejects review actions on already-reviewed requests', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Rejected]);

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request))->assertForbidden();
    $this->actingAs($admin)->patch(route('admin.requests.reject', $request), ['rejection_reason' => 'x'])->assertForbidden();
});
