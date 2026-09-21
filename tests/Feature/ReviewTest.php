<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\ReviewException;
use App\Models\MaintenanceRequest;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;

function completedRequestFor(User $customer): MaintenanceRequest
{
    $request = MaintenanceRequest::factory()->create([
        'user_id' => $customer->id,
        'status' => RequestStatus::Completed,
    ]);

    return $request->refresh();
}

it('lets the owner review a completed job', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $request = completedRequestFor($customer);

    $this->actingAs($customer)->post(route('requests.reviews.store', $request), [
        'rating' => 5,
        'comment' => 'Technician was professional and arrived on time.',
    ])->assertRedirect();

    $review = Review::first();

    expect($review->rating)->toBe(5)
        ->and($review->user_id)->toBe($customer->id)
        ->and($review->maintenance_request_id)->toBe($request->id);
});

it('refuses reviews from non-owners and staff (BR-009)', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $stranger = User::factory()->create(['role' => UserRole::Customer]);
    $technician = User::factory()->create(['role' => UserRole::Technician]);
    $request = completedRequestFor($customer);

    $this->actingAs($stranger)->post(route('requests.reviews.store', $request), ['rating' => 5])
        ->assertNotFound();

    $this->actingAs($technician)->post(route('requests.reviews.store', $request), ['rating' => 5])
        ->assertNotFound();

    expect(fn () => app(ReviewService::class)->submit($request->refresh(), $stranger, 5))
        ->toThrow(ReviewException::class);

    expect(Review::count())->toBe(0);
});

it('refuses reviews for unfinished jobs and duplicates', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $scheduled = MaintenanceRequest::factory()->create([
        'user_id' => $customer->id,
        'status' => RequestStatus::Scheduled,
    ]);

    $this->actingAs($customer)->post(route('requests.reviews.store', $scheduled), ['rating' => 5])
        ->assertSessionHas('error');

    $request = completedRequestFor($customer);
    app(ReviewService::class)->submit($request, $customer, 4);

    $this->actingAs($customer)->post(route('requests.reviews.store', $request), ['rating' => 5])
        ->assertSessionHas('error');

    expect(Review::count())->toBe(1);
});

it('validates the rating range', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $request = completedRequestFor($customer);

    foreach ([0, 6] as $rating) {
        $this->actingAs($customer)->post(route('requests.reviews.store', $request), ['rating' => $rating])
            ->assertSessionHasErrors('rating');
    }

    foreach ([1, 5] as $rating) {
        $fresh = completedRequestFor($customer);
        $this->actingAs($customer)->post(route('requests.reviews.store', $fresh), ['rating' => $rating])
            ->assertRedirect();
    }

    expect(Review::count())->toBe(2);
});

it('lists reviews for staff with a rating filter', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $request = completedRequestFor($customer);
    app(ReviewService::class)->submit($request, $customer, 5, 'Excellent!');

    $this->actingAs($admin)->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertSee('Excellent!');

    $this->actingAs($admin)->get(route('admin.reviews.index', ['rating' => 1]))
        ->assertOk()
        ->assertDontSee('Excellent!');

    $this->actingAs($customer)->get(route('admin.reviews.index'))->assertForbidden();
});
