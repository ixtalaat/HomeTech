<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use App\Services\TechnicianAssignmentService;

function skilledTechnician(Service $service): Technician
{
    $technician = Technician::factory()->create();
    $technician->categories()->sync([$service->service_category_id]);

    return $technician->refresh();
}

it('assigns an eligible technician to an approved request', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->approved()->create();
    $technician = skilledTechnician($request->service);

    $this->actingAs($admin)->patch(route('admin.requests.assign', $request), [
        'technician_id' => $technician->id,
    ])->assertRedirect(route('admin.requests.show', $request));

    expect($request->refresh()->status)->toBe(RequestStatus::TechnicianAssigned)
        ->and($request->technician_id)->toBe($technician->id);
});

it('rejects assignment when the technician lacks the category skill (BR-001)', function () {
    $service = app(TechnicianAssignmentService::class);
    $request = MaintenanceRequest::factory()->approved()->create();
    $technician = Technician::factory()->create();

    expect(fn () => $service->assign($request, $technician))->toThrow(TechnicianAssignmentException::class);

    expect($request->refresh()->status)->toBe(RequestStatus::Approved)
        ->and($request->technician_id)->toBeNull();
});

it('rejects assignment to rejected or cancelled requests', function (RequestStatus $status) {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->create(['status' => $status]);
    $technician = skilledTechnician($request->service);

    $this->actingAs($admin)->patch(route('admin.requests.assign', $request), [
        'technician_id' => $technician->id,
    ])->assertSessionHas('error');

    expect($request->refresh()->technician_id)->toBeNull();
})->with([
    'rejected' => RequestStatus::Rejected,
    'cancelled' => RequestStatus::Cancelled,
    'pending' => RequestStatus::PendingReview,
]);

it('rejects assignment of inactive technicians', function () {
    $service = app(TechnicianAssignmentService::class);
    $request = MaintenanceRequest::factory()->approved()->create();

    $inactiveProfile = Technician::factory()->inactive()->create();
    $inactiveProfile->categories()->sync([$request->service->service_category_id]);

    expect(fn () => $service->assign($request, $inactiveProfile))->toThrow(TechnicianAssignmentException::class);

    $technician = skilledTechnician($request->service);
    $technician->user->update(['is_active' => false]);

    expect(fn () => $service->assign($request, $technician))->toThrow(TechnicianAssignmentException::class);

    expect($request->refresh()->technician_id)->toBeNull();
});

it('reassigns and unassigns technicians', function () {
    $service = app(TechnicianAssignmentService::class);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = MaintenanceRequest::factory()->approved()->create();
    $first = skilledTechnician($request->service);
    $second = skilledTechnician($request->service);

    $service->assign($request, $first, $admin);
    $service->reassign($request->refresh(), $second, $admin);

    expect($request->refresh()->technician_id)->toBe($second->id)
        ->and($request->status)->toBe(RequestStatus::TechnicianAssigned);

    $service->unassign($request->refresh(), $admin);

    expect($request->refresh()->technician_id)->toBeNull()
        ->and($request->status)->toBe(RequestStatus::Approved);
});

it('lists only eligible technicians for a request', function () {
    $service = app(TechnicianAssignmentService::class);
    $request = MaintenanceRequest::factory()->approved()->create();
    $eligible = skilledTechnician($request->service);
    $unskilled = Technician::factory()->create();
    $inactive = Technician::factory()->inactive()->create();
    $inactive->categories()->sync([$request->service->service_category_id]);

    $ids = $service->eligibleFor($request->refresh())->pluck('id')->all();

    expect($ids)->toContain($eligible->id)
        ->and($ids)->not->toContain($unskilled->id)
        ->and($ids)->not->toContain($inactive->id);
});
