<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('updates the authenticated user profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Updated Name',
        'phone' => '555-0100',
        'email' => 'updated@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'phone' => '555-0100',
        'email' => 'updated@example.com',
    ]);
});

it('changes the authenticated user password', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'Password123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect();

    expect(Hash::check('NewPassword123!', $user->refresh()->password))->toBeTrue();
});
