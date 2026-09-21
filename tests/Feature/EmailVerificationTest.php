<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('redirects unverified users to verification and verifies via signed link', function () {
    $user = User::factory()->unverified()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('requests.index'))->assertRedirect(route('verification.notice'));

    $this->actingAs($user)->get(route('verification.notice'))->assertOk();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('resends the verification email', function () {
    $user = User::factory()->unverified()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->post(route('verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');
});
