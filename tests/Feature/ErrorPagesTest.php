<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders a branded 404 page for unknown routes', function () {
    $this->get('/missing-page-xyz')
        ->assertNotFound()
        ->assertSee('404', false)
        ->assertSee('Page not found', false)
        ->assertSee('Browse services', false);
});

it('renders a branded 403 page for forbidden routes', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->get(route('admin.services.index'))
        ->assertForbidden()
        ->assertSee('403', false)
        ->assertSee('Not allowed', false);
});
