<?php

use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Hash;

/**
 * Create a branch with a manager and one served city.
 *
 * @return array{branch: Branch, manager: User}
 */
function managedBranch(string $city = 'Riyadh'): array
{
    $branch = Branch::factory()->withManager()->create();
    $branch->cities()->create(['name' => $city]);
    $branch->refresh();

    return ['branch' => $branch, 'manager' => $branch->manager];
}

it('lands branch managers on their scoped dashboard', function () {
    ['branch' => $branch, 'manager' => $manager] = managedBranch();
    $service = hourlyService();
    workingTechnician($service, $branch);
    mondayRequest($service, 'Riyadh');
    mondayRequest($service, 'Elsewhere');

    $this->actingAs($manager)->get(route('dashboard'))->assertRedirect(route('branch.dashboard'));

    $response = $this->actingAs($manager)->get(route('branch.dashboard'))->assertOk();

    $response->assertSee($branch->name, false)
        ->assertSee('1', false);
});

it('keeps assigned managers out of the admin area and vice versa', function () {
    ['manager' => $manager] = managedBranch();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($manager)->get(route('admin.branches.index'))->assertRedirect(route('branch.dashboard'));
    $this->actingAs($manager)->get(route('admin.technicians.index'))->assertRedirect(route('branch.dashboard'));
    $this->actingAs($manager)->get(route('admin.requests.index'))->assertRedirect(route('branch.dashboard'));

    $this->actingAs($admin)->get(route('branch.dashboard'))->assertForbidden();
});

it('hides other branches technicians and requests', function () {
    ['branch' => $branch, 'manager' => $manager] = managedBranch('Riyadh');
    $service = hourlyService();
    $ownTech = workingTechnician($service, $branch);

    $otherBranch = staffedBranch('Jeddah');
    $foreignTech = workingTechnician($service, $otherBranch);
    $foreignRequest = mondayRequest($service, 'Jeddah');

    $this->actingAs($manager)->get(route('branch.technicians.show', $foreignTech))->assertNotFound();
    $this->actingAs($manager)->get(route('branch.technicians.schedule.edit', $foreignTech))->assertNotFound();
    $this->actingAs($manager)->get(route('branch.requests.show', $foreignRequest))->assertNotFound();

    $this->actingAs($manager)->get(route('branch.technicians.show', $ownTech))->assertOk();
    $this->actingAs($manager)->get(route('branch.technicians.schedule.edit', $ownTech))->assertOk();
});

it('lets super admins assign, change and remove managers', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create(['name' => 'Riyadh']);
    $first = User::factory()->create(['role' => UserRole::Manager]);
    $second = User::factory()->create(['role' => UserRole::Manager]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    // Assign.
    $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
        'name' => 'Riyadh',
        'manager_mode' => 'existing',
        'manager_user_id' => $first->id,
    ])->assertRedirect();

    expect($branch->refresh()->manager_user_id)->toBe($first->id);

    // A non-manager account is refused.
    $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
        'name' => 'Riyadh',
        'manager_mode' => 'existing',
        'manager_user_id' => $customer->id,
    ])->assertSessionHas('error');

    // Change.
    $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
        'name' => 'Riyadh',
        'manager_mode' => 'existing',
        'manager_user_id' => $second->id,
    ])->assertRedirect();

    expect($branch->refresh()->manager_user_id)->toBe($second->id);

    // One account cannot manage two branches.
    $other = Branch::factory()->create(['name' => 'Jeddah']);

    $this->actingAs($admin)->put(route('admin.branches.update', $other), [
        'name' => 'Jeddah',
        'manager_mode' => 'existing',
        'manager_user_id' => $second->id,
    ])->assertSessionHasErrors('manager_user_id');

    // Remove keeps the account usable elsewhere.
    $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
        'name' => 'Riyadh',
        'manager_mode' => 'none',
    ])->assertRedirect();

    expect($branch->refresh()->manager_user_id)->toBeNull()
        ->and($second->refresh()->role)->toBe(UserRole::Manager);
});

it('lets super admins create a manager account inline', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create(['name' => 'Riyadh']);

    $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
        'name' => 'Riyadh',
        'manager_mode' => 'new',
        'manager_name' => 'Layla Hassan',
        'manager_email' => 'layla@hometech.test',
        'manager_password' => 'password123',
    ])->assertRedirect();

    $manager = User::where('email', 'layla@hometech.test')->firstOrFail();

    expect($manager->role)->toBe(UserRole::Manager)
        ->and($branch->refresh()->manager_user_id)->toBe($manager->id);
});

it('lets managers approve and auto-assign inside their branch', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    ['branch' => $branch, 'manager' => $manager] = managedBranch('Riyadh');
    $service = hourlyService();
    $technician = workingTechnician($service, $branch);
    $request = mondayRequest($service, 'Riyadh');
    $request->update(['status' => RequestStatus::PendingReview]);

    $this->actingAs($manager)->patch(route('branch.requests.approve', $request), [
        'admin_note' => 'Looks good.',
    ])->assertRedirect(route('branch.requests.show', $request))
        ->assertSessionHas('success')
        ->assertSessionHas('status');

    expect($request->refresh()->technician_id)->toBe($technician->id)
        ->and($request->status)->toBe(RequestStatus::Scheduled);
});

it('forbids managers from reviewing other branches requests', function () {
    ['manager' => $manager] = managedBranch('Riyadh');
    $service = hourlyService();
    $foreign = mondayRequest($service, 'Nowhere');

    $this->actingAs($manager)->patch(route('branch.requests.approve', $foreign))
        ->assertForbidden();

    expect($foreign->refresh()->status)->toBe(RequestStatus::Approved);
});

it('refuses assigning technicians from other branches', function () {
    ['manager' => $manager] = managedBranch('Riyadh');
    $service = hourlyService();
    $foreignBranch = staffedBranch('Jeddah');
    $foreignTech = workingTechnician($service, $foreignBranch);

    $request = mondayRequest($service, 'Riyadh');
    $request->update(['status' => RequestStatus::Approved]);

    $this->actingAs($manager)->patch(route('branch.requests.assign', $request), [
        'technician_id' => $foreignTech->id,
    ])->assertSessionHasErrors('technician_id');

    expect($request->refresh()->technician_id)->toBeNull();
});

it('lets managers edit their technicians schedules', function () {
    ['branch' => $branch, 'manager' => $manager] = managedBranch('Riyadh');
    $service = hourlyService();
    $technician = workingTechnician($service, $branch);

    $days = array_map(fn (int $day): array => [
        'day_of_week' => $day,
        'is_working' => '1',
        'start_time' => '09:00',
        'end_time' => '18:00',
    ], range(0, 6));

    $this->actingAs($manager)->put(route('branch.technicians.schedule.update', $technician), [
        'days' => $days,
    ])->assertRedirect(route('branch.technicians.show', $technician));

    expect(substr((string) $technician->schedules()->where('day_of_week', 1)->first()->start_time, 0, 5))->toBe('09:00');
});

it('manages manager accounts from the admin managers page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    ['branch' => $branch, 'manager' => $manager] = managedBranch('Riyadh');

    $this->actingAs($admin)->get(route('admin.managers.index'))
        ->assertOk()
        ->assertSee($manager->name, false)
        ->assertSee($branch->name, false);

    // Toggle employment status.
    $this->actingAs($admin)->patch(route('admin.managers.toggle-status', $manager))->assertRedirect();

    expect($manager->refresh()->is_active)->toBeFalse();

    // Non-manager accounts cannot be toggled here.
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($admin)->patch(route('admin.managers.toggle-status', $customer))->assertNotFound();

    // Unassign keeps the account.
    $this->actingAs($admin)->patch(route('admin.managers.unassign', $manager))->assertRedirect();

    expect($branch->refresh()->manager_user_id)->toBeNull()
        ->and($manager->refresh()->role)->toBe(UserRole::Manager);
});

it('keeps non-staff out of manager administration', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $technician = User::factory()->create(['role' => UserRole::Technician]);

    $this->actingAs($customer)->get(route('admin.managers.index'))->assertForbidden();
    $this->actingAs($technician)->get(route('admin.managers.index'))->assertForbidden();
});

it('confines assigned managers to their branch area', function () {
    ['manager' => $manager] = managedBranch('Riyadh');

    // Global admin pages bounce assigned managers to their dashboard.
    $this->actingAs($manager)->get(route('admin.dashboard'))->assertRedirect(route('branch.dashboard'));
    $this->actingAs($manager)->get(route('admin.branches.index'))->assertRedirect(route('branch.dashboard'));
    $this->actingAs($manager)->get(route('admin.managers.index'))->assertRedirect(route('branch.dashboard'));

    // Unassigned managers keep the legacy global staff access.
    $plain = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($plain)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($plain)->get(route('admin.managers.index'))->assertOk();
    $this->actingAs($plain)->get(route('branch.dashboard'))->assertNotFound();
});

it('shows branch revenue from paid invoices', function () {
    ['branch' => $branch] = managedBranch('Riyadh');
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $workOrder = completedWorkOrderWithCharges();
    $workOrder->request->address->update(['city' => 'Riyadh']);
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);
    app(InvoiceService::class)->issue($invoice->refresh(), $admin);
    app(PaymentService::class)->pay($invoice->refresh(), (float) $invoice->refresh()->total, PaymentMethod::Cash, $admin);

    $overview = app(BranchService::class)->overview($branch->refresh());

    expect($overview['revenue_30d'])->toBe((float) $invoice->refresh()->total);

    // Other branches stay excluded.
    $other = staffedBranch('Jeddah');
    $otherOverview = app(BranchService::class)->overview($other);

    expect($otherOverview['revenue_30d'])->toBe(0.0);
});
it('flags branches without a manager on the admin dashboard', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $branch = Branch::factory()->create(['name' => 'Tabuk']);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    $response->assertSee('Tabuk', false);

    $branch->update(['manager_user_id' => User::factory()->create(['role' => UserRole::Manager])->id]);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Tabuk', false);
});

it('edits manager data including branch moves', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    ['branch' => $branch, 'manager' => $manager] = managedBranch('Riyadh');
    $other = Branch::factory()->create(['name' => 'Jeddah']);

    $this->actingAs($admin)->get(route('admin.managers.edit', $manager))->assertOk();

    // Update details and move to another branch.
    $this->actingAs($admin)->put(route('admin.managers.update', $manager), [
        'name' => 'Layla Updated',
        'email' => 'layla.updated@hometech.test',
        'password' => 'newpassword123',
        'is_active' => '1',
        'branch_id' => $other->id,
    ])->assertRedirect(route('admin.managers.index'));

    $manager->refresh();

    expect($manager->name)->toBe('Layla Updated')
        ->and($manager->email)->toBe('layla.updated@hometech.test')
        ->and(Hash::check('newpassword123', $manager->password))->toBeTrue()
        ->and($other->refresh()->manager_user_id)->toBe($manager->id)
        ->and($branch->refresh()->manager_user_id)->toBeNull();

    // Blank password keeps the old one; empty branch unassigns.
    $this->actingAs($admin)->put(route('admin.managers.update', $manager), [
        'name' => 'Layla Updated',
        'email' => 'layla.updated@hometech.test',
        'password' => '',
        'is_active' => '1',
        'branch_id' => '',
    ])->assertRedirect();

    expect(Hash::check('newpassword123', $manager->refresh()->password))->toBeTrue()
        ->and($other->refresh()->manager_user_id)->toBeNull();

    // Occupied branches and duplicate emails are refused.
    $taken = Branch::factory()->withManager()->create();

    $this->actingAs($admin)->put(route('admin.managers.update', $manager), [
        'name' => 'Layla Updated',
        'email' => $admin->email,
        'branch_id' => (string) $taken->id,
    ])->assertSessionHasErrors(['email', 'branch_id']);

    // Non-managers cannot be edited here.
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($admin)->get(route('admin.managers.edit', $customer))->assertNotFound();
});
