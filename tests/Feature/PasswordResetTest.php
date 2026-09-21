<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Password;

it('sends a reset link and resets the password', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-123'])
        ->assertRedirect(route('dashboard'));
});

it('throttles repeated login attempts', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    for ($i = 0; $i < 30; $i++) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertStatus(429);
});
