<?php

use App\Enums\UserRole;
use App\Models\Technician;
use App\Models\User;

it('smoke: every role can log in and reach its home pages', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $technician = Technician::factory()->create();
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $cases = [
        [$admin, ['admin.dashboard', 'admin.requests.index', 'admin.invoices.index']],
        [$manager, ['admin.dashboard', 'admin.reports.revenue']],
        [$technician->user, ['technician.jobs.index']],
        [$customer, ['dashboard', 'requests.index', 'invoices.index']],
    ];

    foreach ($cases as [$user, $routes]) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->post(route('logout'))->assertRedirect(route('login'));
    }
});
