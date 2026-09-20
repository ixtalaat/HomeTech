<?php

use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\RequestStatusService;

it('rejects illegal status transitions', function (RequestStatus $from, RequestStatus $to) {
    $service = app(RequestStatusService::class);
    $request = MaintenanceRequest::factory()->create(['status' => $from]);

    expect(fn () => $service->transition($request, $to))->toThrow(InvalidStatusTransitionException::class);

    expect($request->refresh()->status)->toBe($from);
})->with([
    'pending to completed' => [RequestStatus::PendingReview, RequestStatus::Completed],
    'pending to paid' => [RequestStatus::PendingReview, RequestStatus::Paid],
    'rejected to approved' => [RequestStatus::Rejected, RequestStatus::Approved],
    'rejected to pending' => [RequestStatus::Rejected, RequestStatus::PendingReview],
    'approved to pending' => [RequestStatus::Approved, RequestStatus::PendingReview],
    'approved to completed (locked until work orders)' => [RequestStatus::Approved, RequestStatus::Completed],
]);

it('allows legal review transitions and records history', function () {
    $service = app(RequestStatusService::class);
    $admin = User::factory()->create();
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);

    $service->transition($request, RequestStatus::Approved, $admin, 'OK');

    expect($request->refresh()->status)->toBe(RequestStatus::Approved);

    $history = $request->statusHistories()->latest()->first();
    expect($history->from_status)->toBe(RequestStatus::PendingReview->value)
        ->and($history->status)->toBe(RequestStatus::Approved->value)
        ->and($history->changed_by)->toBe($admin->id)
        ->and($history->reason)->toBe('OK');
});

it('prevents rejected requests from being assigned', function () {
    $service = app(RequestStatusService::class);
    $request = MaintenanceRequest::factory()->rejected()->create();

    // Technician assignment (Epic 5/6) builds on approved requests only.
    expect($request->status)->toBe(RequestStatus::Rejected)
        ->and($service->canTransition($request->status, RequestStatus::TechnicianAssigned))->toBeFalse()
        ->and($service->canTransition($request->status, RequestStatus::Approved))->toBeFalse();
});
