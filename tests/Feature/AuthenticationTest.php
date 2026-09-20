<?php

use App\Enums\UserRole;
use App\Models\User;

it('registers a customer and authenticates them', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Customer',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs(User::where('email', 'jane@example.com')->first());
    expect(User::where('email', 'jane@example.com')->value('role'))->toBe(UserRole::Customer->value);
});

it('rejects invalid registration data', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('logs in and logs out a user', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
