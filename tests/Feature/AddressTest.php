<?php

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\User;

it('allows customers to manage their own addresses', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('addresses.store'), [
        'title' => 'Home',
        'street' => '12 Nile St, Apt 4',
        'city' => 'Cairo',
        'notes' => 'Near metro',
    ])->assertRedirect(route('addresses.index'));

    $address = Address::first();
    expect($address->user_id)->toBe($customer->id)
        ->and($address->is_default)->toBeTrue();

    $this->actingAs($customer)->get(route('addresses.index'))->assertOk()->assertSee('Home');

    $this->actingAs($customer)->put(route('addresses.update', $address), [
        'title' => 'Home Updated',
        'street' => '12 Nile St, Apt 4',
        'city' => 'Giza',
    ])->assertRedirect(route('addresses.index'));

    expect($address->refresh()->city)->toBe('Giza');

    $this->actingAs($customer)->delete(route('addresses.destroy', $address))->assertRedirect(route('addresses.index'));

    expect(Address::count())->toBe(0);
});

it('enforces address ownership between customers', function () {
    $customerA = User::factory()->create(['role' => UserRole::Customer]);
    $customerB = User::factory()->create(['role' => UserRole::Customer]);
    $address = Address::factory()->create(['user_id' => $customerA->id]);

    $this->actingAs($customerB)->get(route('addresses.edit', $address))->assertNotFound();
    $this->actingAs($customerB)->put(route('addresses.update', $address), [
        'title' => 'Hijacked',
        'street' => 'X',
        'city' => 'Y',
    ])->assertNotFound();
    $this->actingAs($customerB)->delete(route('addresses.destroy', $address))->assertNotFound();
    $this->actingAs($customerB)->patch(route('addresses.set-default', $address))->assertNotFound();

    expect($address->refresh()->title)->not->toBe('Hijacked');
});

it('keeps exactly one default address per customer', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $first = Address::factory()->create(['user_id' => $customer->id, 'is_default' => true]);
    $second = Address::factory()->create(['user_id' => $customer->id, 'is_default' => false]);

    $this->actingAs($customer)->patch(route('addresses.set-default', $second))->assertRedirect();

    expect($first->refresh()->is_default)->toBeFalse()
        ->and($second->refresh()->is_default)->toBeTrue()
        ->and(Address::where('user_id', $customer->id)->where('is_default', true)->count())->toBe(1);
});

it('promotes another address to default when the default is deleted', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $first = Address::factory()->create(['user_id' => $customer->id, 'is_default' => true]);
    $second = Address::factory()->create(['user_id' => $customer->id, 'is_default' => false]);

    $this->actingAs($customer)->delete(route('addresses.destroy', $first))->assertRedirect();

    expect($second->refresh()->is_default)->toBeTrue();
});

it('validates address input', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('addresses.store'), [
        'title' => '',
        'street' => '',
        'city' => '',
    ])->assertSessionHasErrors(['title', 'street', 'city']);
});

it('requires authentication for address routes', function () {
    $this->get(route('addresses.index'))->assertRedirect(route('login'));
});
