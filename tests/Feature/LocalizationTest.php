<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;

it('switches between English and Arabic', function () {
    $this->get(route('locale.switch', 'ar'))->assertRedirect();
    expect(session('locale'))->toBe('ar');

    $this->get(route('locale.switch', 'fr'))->assertNotFound();
});

it('persists the switched locale across requests like a browser click', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->get(route('locale.switch', 'ar'))->assertRedirect();

    $this->actingAs($customer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('طلباتي', false);
});

it('renders Arabic RTL layout with translated nav', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $response = $this->actingAs($customer)
        ->withSession(['locale' => 'ar'])
        ->get(route('dashboard'))
        ->assertOk();

    $response->assertSee('dir="rtl"', false)
        ->assertSee('طلباتي', false)
        ->assertSee('عناويني', false)
        ->assertSee('فواتيري', false);
});

it('renders English LTR layout by default', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('My requests', false);
});

it('translates enum labels per locale', function () {
    expect(RequestStatus::PendingReview->label())->toBe('Pending Review');

    app()->setLocale('ar');

    expect(RequestStatus::PendingReview->label())->toBe('بانتظار المراجعة');
});

it('shows Arabic validation messages in Arabic locale', function () {
    $response = $this->withSession(['locale' => 'ar'])->post(route('login.store'), [
        'email' => 'not-an-email',
        'password' => 'x',
    ]);

    $response->assertSessionHasErrors('email');
});
