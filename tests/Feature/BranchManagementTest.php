<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Technician;
use App\Models\User;

it('creates a branch with cities', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post(route('admin.branches.store'), [
        'name' => 'Riyadh',
        'priority' => 50,
        'cities' => 'Riyadh, Al Kharj, riyadh ',
    ])->assertRedirect(route('admin.branches.index'));

    $branch = Branch::where('name', 'Riyadh')->firstOrFail();

    expect($branch->priority)->toBe(50)
        ->and($branch->cities->pluck('name')->sort()->values()->all())->toBe(['Al Kharj', 'Riyadh']);
});

it('moves cities between branches on update', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $riyadh = Branch::factory()->create(['name' => 'Riyadh']);
    $riyadh->cities()->create(['name' => 'Riyadh']);
    $jeddah = Branch::factory()->create(['name' => 'Jeddah']);

    $this->actingAs($admin)->put(route('admin.branches.update', $jeddah), [
        'name' => 'Jeddah',
        'cities' => 'Jeddah, Riyadh',
    ])->assertRedirect();

    expect($jeddah->cities()->pluck('name')->sort()->values()->all())->toBe(['Jeddah', 'Riyadh'])
        ->and($riyadh->cities()->count())->toBe(0);
});

it('refuses to delete branches still in use', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create();
    $branch->cities()->create(['name' => 'Taif']);
    Technician::factory()->create(['branch_id' => $branch->id]);

    $this->actingAs($admin)->delete(route('admin.branches.destroy', $branch))
        ->assertSessionHas('error');

    expect(Branch::find($branch->id))->not->toBeNull();

    $empty = Branch::factory()->create();

    $this->actingAs($admin)->delete(route('admin.branches.destroy', $empty))
        ->assertRedirect(route('admin.branches.index'));

    expect(Branch::find($empty->id))->toBeNull();
});

it('renders the branch, schedule and technician pages', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create();
    $technician = Technician::factory()->create(['branch_id' => $branch->id]);

    $this->actingAs($admin)->get(route('admin.branches.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.branches.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.branches.edit', $branch))->assertOk();
    $this->actingAs($admin)->get(route('admin.technicians.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.technicians.edit', $technician))->assertOk();
    $this->actingAs($admin)->get(route('admin.technicians.show', $technician))->assertOk();
    $this->actingAs($admin)->get(route('admin.technicians.schedule.edit', $technician))->assertOk();
});
it('stores the technician branch and seeds a default schedule', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create(['name' => 'Riyadh']);

    $this->actingAs($admin)->post(route('admin.technicians.store'), [
        'name' => 'Ahmed Hassan',
        'email' => 'ahmed-schedule@hometech.test',
        'password' => 'password123',
        'branch_id' => $branch->id,
    ])->assertRedirect();

    $technician = Technician::whereHas('user', fn ($query) => $query->where('email', 'ahmed-schedule@hometech.test'))->firstOrFail();

    expect($technician->branch_id)->toBe($branch->id)
        ->and($technician->schedules)->toHaveCount(7)
        ->and($technician->schedules->firstWhere('day_of_week', 5)->is_working)->toBeFalse()
        ->and(substr((string) $technician->schedules->firstWhere('day_of_week', 6)->start_time, 0, 5))->toBe('10:00');
});

it('replaces the weekly schedule with validation', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $technician = Technician::factory()->create();

    $days = array_map(fn (int $day): array => [
        'day_of_week' => $day,
        'is_working' => $day === 5 ? '0' : '1',
        'start_time' => '09:00',
        'end_time' => '18:00',
    ], range(0, 6));

    $this->actingAs($admin)->put(route('admin.technicians.schedule.update', $technician), [
        'days' => $days,
    ])->assertRedirect(route('admin.technicians.show', $technician));

    expect($technician->schedules()->count())->toBe(7)
        ->and($technician->schedules()->where('day_of_week', 5)->first()->is_working)->toBeFalse()
        ->and(substr((string) $technician->schedules()->where('day_of_week', 1)->first()->start_time, 0, 5))->toBe('09:00');

    $days[1]['end_time'] = '08:00';

    $this->actingAs($admin)->put(route('admin.technicians.schedule.update', $technician), [
        'days' => $days,
    ])->assertSessionHas('error');
});
