<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function validRequestPayload(Service $service, Address $address): array
{
    return [
        'service_id' => $service->id,
        'address_id' => $address->id,
        'description' => 'My AC is running but is not cooling the room.',
        'preferred_date' => now()->addDays(3)->format('Y-m-d'),
        'preferred_time' => '10:00',
    ];
}

it('creates a maintenance request with pending review status', function () {
    Storage::fake('public');
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $service = Service::factory()->create(['is_active' => true]);
    $address = Address::factory()->create(['user_id' => $customer->id]);

    $this->actingAs($customer)->post(route('requests.store'), [
        ...validRequestPayload($service, $address),
        'photos' => [fakePngPhoto()],
    ])->assertRedirect();

    $request = MaintenanceRequest::first();
    expect($request->status)->toBe(RequestStatus::PendingReview)
        ->and($request->user_id)->toBe($customer->id)
        ->and($request->photos)->toHaveCount(1);

    Storage::disk('public')->assertExists($request->photos[0]);

    expect($request->statusHistories)->toHaveCount(1)
        ->and($request->statusHistories->first()->status)->toBe(RequestStatus::PendingReview->value);
});

it('rejects requests for inactive services or inactive categories', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $address = Address::factory()->create(['user_id' => $customer->id]);
    $inactiveService = Service::factory()->inactive()->create();

    $this->actingAs($customer)->post(route('requests.store'), validRequestPayload($inactiveService, $address))
        ->assertSessionHasErrors('service_id');

    expect(MaintenanceRequest::count())->toBe(0);
});

it('rejects requests using another customer address', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $other = User::factory()->create(['role' => UserRole::Customer]);
    $service = Service::factory()->create(['is_active' => true]);
    $foreignAddress = Address::factory()->create(['user_id' => $other->id]);

    $this->actingAs($customer)->post(route('requests.store'), validRequestPayload($service, $foreignAddress))
        ->assertSessionHasErrors('address_id');

    expect(MaintenanceRequest::count())->toBe(0);
});

it('rejects past preferred dates', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $service = Service::factory()->create(['is_active' => true]);
    $address = Address::factory()->create(['user_id' => $customer->id]);

    $this->actingAs($customer)->post(route('requests.store'), [
        ...validRequestPayload($service, $address),
        'preferred_date' => now()->subDay()->format('Y-m-d'),
    ])->assertSessionHasErrors('preferred_date');

    expect(MaintenanceRequest::count())->toBe(0);
});

it('prevents customers from viewing other customers requests', function () {
    $customerA = User::factory()->create(['role' => UserRole::Customer]);
    $customerB = User::factory()->create(['role' => UserRole::Customer]);
    $request = MaintenanceRequest::factory()->create(['user_id' => $customerA->id]);

    $this->actingAs($customerB)->get(route('requests.show', $request))->assertNotFound();
    $this->actingAs($customerA)->get(route('requests.show', $request))->assertOk();
});

it('requires authentication for request routes', function () {
    $this->get(route('requests.index'))->assertRedirect(route('login'));
    $this->get(route('requests.create'))->assertRedirect(route('login'));
});
