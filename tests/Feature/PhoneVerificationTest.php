<?php

use App\Enums\UserRole;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\WhatsAppService;

function whatsappFake(): object
{
    return new class extends WhatsAppService
    {
        public array $sent = [];

        public function send(string $to, string $message): void
        {
            $this->sent[] = compact('to', 'message');
        }

        public function lastCode(): ?string
        {
            if ($this->sent === []) {
                return null;
            }

            preg_match('/\b(\d{6})\b/', end($this->sent)['message'], $matches);

            return $matches[1] ?? null;
        }
    };
}

function verifiedCustomer(): User
{
    return User::factory()->create([
        'role' => UserRole::Customer,
        'phone' => '+201000000001',
    ]);
}

it('sends a WhatsApp code and verifies the phone', function () {
    $fake = whatsappFake();
    $this->app->bind(WhatsAppService::class, fn () => $fake);

    $customer = verifiedCustomer();

    $this->actingAs($customer)->post(route('phone.send-code'))->assertRedirect();

    expect(PhoneVerification::where('user_id', $customer->id)->count())->toBe(1);

    $code = $fake->lastCode();
    expect($code)->not->toBeNull();

    $this->actingAs($customer)->post(route('phone.verify'), ['code' => $code])->assertRedirect();

    expect($customer->refresh()->phone_verified_at)->not->toBeNull()
        ->and(PhoneVerification::where('user_id', $customer->id)->count())->toBe(0);
});

it('rejects wrong, expired, and over-attempted codes', function () {
    $fake = whatsappFake();
    $this->app->bind(WhatsAppService::class, fn () => $fake);

    $customer = verifiedCustomer();
    $service = app(PhoneVerificationService::class);

    $service->sendCode($customer);

    $this->actingAs($customer)->post(route('phone.verify'), ['code' => '000000'])
        ->assertSessionHas('error');

    expect($customer->refresh()->phone_verified_at)->toBeNull();

    // Expired.
    PhoneVerification::where('user_id', $customer->id)->update(['expires_at' => now()->subMinute()]);
    $this->actingAs($customer)->post(route('phone.verify'), ['code' => $fake->lastCode() ?? '000000'])
        ->assertSessionHas('error');

    // Too many attempts.
    $service->sendCode($customer->refresh());
    PhoneVerification::where('user_id', $customer->id)->update(['attempts' => 5]);
    $this->actingAs($customer)->post(route('phone.verify'), ['code' => '000000'])
        ->assertSessionHas('error');
    expect(PhoneVerification::where('user_id', $customer->id)->count())->toBe(0);
});

it('refuses codes without a phone or when already verified', function () {
    $this->app->bind(WhatsAppService::class, fn () => whatsappFake());

    $noPhone = User::factory()->create(['role' => UserRole::Customer, 'phone' => null]);
    $this->actingAs($noPhone)->post(route('phone.send-code'))->assertSessionHas('error');

    $verified = User::factory()->create([
        'role' => UserRole::Customer,
        'phone' => '+201000000002',
        'phone_verified_at' => now(),
    ]);
    $this->actingAs($verified)->post(route('phone.send-code'))->assertSessionHas('error');
});
