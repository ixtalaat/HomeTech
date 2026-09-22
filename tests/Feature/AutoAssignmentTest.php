<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\Appointment;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Services\TechnicianAssignmentService;
use Carbon\Carbon;

it('auto-assigns an available technician and books the preferred slot', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $technician = workingTechnician($service, $branch);
    $request = mondayRequest($service);

    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician']?->id)->toBe($technician->id)
        ->and($result['reason'])->toBeNull()
        ->and($request->refresh()->status)->toBe(RequestStatus::Scheduled)
        ->and($request->technician_id)->toBe($technician->id)
        ->and($request->appointment->start_time)->toBe('10:00:00')
        ->and($request->appointment->end_time)->toBe('11:00:00');
});

it('refuses technicians who are off duty that day', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $technician = Technician::factory()->create(['branch_id' => $branch->id]);
    $technician->categories()->sync([$service->service_category_id]);
    $technician->schedules()->create([
        'day_of_week' => Carbon::MONDAY,
        'is_working' => false,
        'start_time' => null,
        'end_time' => null,
    ]);
    $request = mondayRequest($service);

    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('off duty')
        ->and($request->refresh()->status)->toBe(RequestStatus::Approved)
        ->and($request->technician_id)->toBeNull();
});

it('refuses slots outside working hours', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $technician = Technician::factory()->create(['branch_id' => $branch->id]);
    $technician->categories()->sync([$service->service_category_id]);
    aroundTheClock($technician->refresh());
    $technician->schedules()->where('day_of_week', Carbon::MONDAY)->update([
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);
    $request = mondayRequest($service);
    $request->update(['preferred_time' => '14:00']);

    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('outside working hours');
});

it('picks another technician on overlap, or fails with a reason', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $busy = workingTechnician($service, $branch);
    $free = workingTechnician($service, $branch);
    $lonely = workingTechnician($service, $branch);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    Appointment::factory()->create([
        'technician_id' => $busy->id,
        'date' => $date,
        'start_time' => '10:30',
        'end_time' => '11:30',
    ]);

    $request = mondayRequest($service);
    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    // Free and lonely tie on load: the lowest id wins deterministically.
    expect($result['technician']?->id)->toBe($free->id);

    Appointment::factory()->create([
        'technician_id' => $lonely->id,
        'date' => $date,
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $second = mondayRequest($service);
    $result = app(TechnicianAssignmentService::class)->autoAssign($second);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('conflicting')
        ->and($second->refresh()->status)->toBe(RequestStatus::Approved);
});

it('enforces the daily limit of two requests', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $technician = workingTechnician($service, $branch);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    // Two non-overlapping bookings: the limit is hit without any conflict.
    Appointment::factory()->create(['technician_id' => $technician->id, 'date' => $date, 'start_time' => '08:00', 'end_time' => '09:00']);
    Appointment::factory()->create(['technician_id' => $technician->id, 'date' => $date, 'start_time' => '09:00', 'end_time' => '10:00']);

    $request = mondayRequest($service);
    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('daily limit')
        ->and($request->refresh()->technician_id)->toBeNull();
});

it('balances load fairly and breaks ties deterministically', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    $loaded = workingTechnician($service, $branch);
    $idle = workingTechnician($service, $branch);
    $date = Carbon::parse('next monday')->format('Y-m-d');

    Appointment::factory()->create(['technician_id' => $loaded->id, 'date' => $date, 'start_time' => '08:00', 'end_time' => '09:00']);

    $first = mondayRequest($service);
    $result = app(TechnicianAssignmentService::class)->autoAssign($first);

    expect($result['technician']?->id)->toBe($idle->id);

    // Equal day load and equal history: the lowest id wins, every time.
    $freshA = workingTechnician($service, $branch);
    $freshB = workingTechnician($service, $branch);

    MaintenanceRequest::factory()->create(['technician_id' => $freshA->id, 'service_id' => $service->id]);

    $second = mondayRequest($service);
    $result = app(TechnicianAssignmentService::class)->autoAssign($second);

    expect($result['technician']?->id)->toBe($freshB->id);
});

it('falls back to other branches by priority', function () {
    $service = hourlyService();
    $home = staffedBranch('Riyadh', 50);
    $jeddah = staffedBranch('Jeddah', 40);
    $dammam = staffedBranch('Dammam', 30);

    $homeTech = Technician::factory()->create(['branch_id' => $home->id]);
    $homeTech->categories()->sync([$service->service_category_id]);
    $homeTech->schedules()->create([
        'day_of_week' => Carbon::MONDAY,
        'is_working' => false,
        'start_time' => null,
        'end_time' => null,
    ]);

    $dammamTech = workingTechnician($service, $dammam);
    $jeddahTech = workingTechnician($service, $jeddah);

    $request = mondayRequest($service, 'Riyadh');
    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician']?->id)->toBe($jeddahTech->id)
        ->and($result['technician']?->id)->not->toBe($dammamTech->id);
});

it('explains unknown cities and branchless technicians', function () {
    $service = hourlyService();
    staffedBranch('Riyadh');

    $request = mondayRequest($service, 'Atlantis');
    $result = app(TechnicianAssignmentService::class)->autoAssign($request);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('Atlantis');

    $branchless = Technician::factory()->create(['branch_id' => null]);
    $branchless->categories()->sync([$service->service_category_id]);
    aroundTheClock($branchless->refresh());

    $local = mondayRequest($service, 'Riyadh');
    $result = app(TechnicianAssignmentService::class)->autoAssign($local);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('no skilled technicians');
});

it('refuses past slots and non-approved requests', function () {
    $service = hourlyService();
    staffedBranch('Riyadh');
    $past = mondayRequest($service, 'Riyadh');
    $past->update(['preferred_date' => now()->subDay()->format('Y-m-d')]);

    $result = app(TechnicianAssignmentService::class)->autoAssign($past);

    expect($result['technician'])->toBeNull()
        ->and($result['reason'])->toContain('in the past');

    $pending = MaintenanceRequest::factory()->create(['status' => RequestStatus::PendingReview]);

    expect(fn () => app(TechnicianAssignmentService::class)->autoAssign($pending))
        ->toThrow(TechnicianAssignmentException::class);
});

it('never double-books one technician for overlapping requests', function () {
    $service = hourlyService();
    $branch = staffedBranch();
    workingTechnician($service, $branch);

    $first = mondayRequest($service);
    $second = mondayRequest($service);

    $assignments = app(TechnicianAssignmentService::class);

    expect($assignments->autoAssign($first)['technician'])->not->toBeNull()
        ->and($assignments->autoAssign($second)['technician'])->toBeNull();

    $date = Carbon::parse('next monday')->format('Y-m-d');

    expect(Appointment::forTechnicianOn($first->technician_id, $date)->blocking()->count())->toBe(1)
        ->and($second->refresh()->status)->toBe(RequestStatus::Approved);
});

it('auto-assigns on admin approval and flashes the outcome', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = hourlyService();
    $branch = staffedBranch();
    $technician = workingTechnician($service, $branch);
    $request = mondayRequest($service);
    $request->update(['status' => RequestStatus::PendingReview]);

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request), [
        'admin_note' => 'Looks good.',
    ])->assertRedirect(route('admin.requests.show', $request))
        ->assertSessionHas('success')
        ->assertSessionHas('status');

    expect(session('status'))->toContain($technician->user->name);

    expect($request->refresh()->technician_id)->toBe($technician->id)
        ->and($request->status)->toBe(RequestStatus::Scheduled);
});

it('leaves approved requests unassigned with the reason when nobody qualifies', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = hourlyService();
    $request = mondayRequest($service, 'Nowhere');
    $request->update(['status' => RequestStatus::PendingReview]);

    $this->actingAs($admin)->patch(route('admin.requests.approve', $request), [
        'admin_note' => 'Looks good.',
    ])->assertRedirect(route('admin.requests.show', $request))
        ->assertSessionHas('success')
        ->assertSessionHas('status');

    expect(session('status'))->toContain('Nowhere');

    expect($request->refresh()->status)->toBe(RequestStatus::Approved)
        ->and($request->technician_id)->toBeNull();
});
