<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

it('stores a push token for the user', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->postJson(route('push-tokens.store'), [
        'token' => 'device-token-123',
        'platform' => 'web',
        'device_name' => 'My laptop',
    ])->assertCreated()->assertJson(['saved' => true]);

    expect($customer->fcmTokens()->where('token', 'device-token-123')->exists())->toBeTrue();
});

it('validates the push token payload', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->postJson(route('push-tokens.store'), [
        'token' => '',
        'platform' => 'carrier-pigeon',
    ])->assertUnprocessable()->assertJsonValidationErrors(['token', 'platform']);
});

it('deletes a push token', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->fcmTokens()->create(['token' => 'old-token']);

    $this->actingAs($customer)->deleteJson(route('push-tokens.destroy'), [
        'token' => 'old-token',
    ])->assertOk();

    expect($customer->fcmTokens()->exists())->toBeFalse();
});

it('exposes the public web-push configuration', function () {
    config(['firebase.web' => [
        'api_key' => 'web-key',
        'auth_domain' => 'hometech-a06d8.firebaseapp.com',
        'project_id' => 'hometech-a06d8',
        'sender_id' => '123',
        'app_id' => '1:123:web:abc',
    ], 'firebase.vapid_key' => 'vapid-key']);

    $this->get(route('firebase.config'))->assertOk()->assertJson([
        'apiKey' => 'web-key',
        'projectId' => 'hometech-a06d8',
        'vapidKey' => 'vapid-key',
    ]);
});

it('sends FCM messages and prunes dead tokens', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->fcmTokens()->createMany([['token' => 'good-token'], ['token' => 'dead-token']]);

    Http::fake([
        'fcm.googleapis.com/*' => Http::sequence()
            ->push(['name' => 'projects/demo/messages/1'], 200)
            ->push(['error' => ['status' => 'UNREGISTERED']], 404),
    ]);

    $service = Mockery::mock(FcmService::class.'[accessToken,isConfigured]', ['/nonexistent/creds.json', 'demo-project']);
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('accessToken')->andReturn('fake-access-token');
    $service->shouldReceive('isConfigured')->andReturn(true);

    expect($service->sendToUser($customer, 'Hi', 'Hello'))->toBe(['sent' => 1, 'failed' => 1]);
    expect($customer->fcmTokens()->pluck('token')->all())->toBe(['good-token']);
});

it('sends nothing when FCM is unconfigured', function () {
    Http::fake();

    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->fcmTokens()->create(['token' => 'some-token']);

    $service = new FcmService('/nonexistent/creds.json', null);

    expect($service->sendToUser($customer, 'Hi', 'Hello'))->toBe(['sent' => 0, 'failed' => 0]);

    Http::assertNothingSent();
});

it('forwards database notifications to push', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $push = Mockery::mock(FcmService::class);
    $push->shouldReceive('sendToUser')->once()->with(
        Mockery::type(User::class),
        Mockery::type('string'),
        'Update',
        Mockery::type('array')
    )->andReturn(['sent' => 1, 'failed' => 0]);
    $this->app->instance(FcmService::class, $push);

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toArray(object $notifiable): array
        {
            return ['type' => 'ping'];
        }
    };

    event(new NotificationSent($customer, $notification, 'database', null));
});

it('prunes dead tokens without delivering anything', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->fcmTokens()->createMany([['token' => 'live-token'], ['token' => 'dead-token']]);

    Http::fake([
        'fcm.googleapis.com/*' => Http::sequence()
            ->push(['name' => 'projects/demo/messages/1'], 200)
            ->push(['error' => ['status' => 'NOT_FOUND']], 404),
    ]);

    $service = Mockery::mock(FcmService::class.'[accessToken,isConfigured]', ['/nonexistent/creds.json', 'demo-project']);
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('accessToken')->andReturn('fake-access-token');
    $service->shouldReceive('isConfigured')->andReturn(true);

    expect($service->pruneStaleTokens())->toBe(['checked' => 2, 'pruned' => 1]);
    expect($customer->fcmTokens()->pluck('token')->all())->toBe(['live-token']);
});

it('reports pruned counts from the command', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $customer->fcmTokens()->create(['token' => 'dead-token']);

    Http::fake([
        'fcm.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'UNREGISTERED']], 404),
    ]);

    $service = Mockery::mock(FcmService::class.'[accessToken,isConfigured]', ['/nonexistent/creds.json', 'demo-project']);
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('accessToken')->andReturn('fake-access-token');
    $service->shouldReceive('isConfigured')->andReturn(true);
    $this->app->instance(FcmService::class, $service);

    $this->artisan('app:prune-stale-tokens')
        ->assertSuccessful()
        ->expectsOutputToContain('pruned 1 stale');

    expect($customer->fcmTokens()->exists())->toBeFalse();
});
