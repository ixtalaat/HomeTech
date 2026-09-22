<?php

use App\Enums\UserRole;
use App\Mail\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('shows the contact support page', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->get(route('support.create'))
        ->assertOk()
        ->assertSee('Contact support', false);
});

it('sends the message to the admin by mail', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin, 'email' => 'admin@hometech.test']);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('support.store'), [
        'subject' => 'Question about my invoice',
        'message' => 'The total looks wrong, please review it.',
    ])->assertRedirect(route('support.create'));

    Mail::assertSent(SupportMessage::class, fn (SupportMessage $mail): bool => $mail->hasTo($admin->email));
});

it('requires a subject and a message', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('support.store'), [
        'subject' => '',
        'message' => '',
    ])->assertSessionHasErrors(['subject', 'message']);
});

it('renders the mailable content', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $mail = new SupportMessage($customer, 'Hello', 'Need help with <b>billing</b>.');

    $mail->assertSeeInHtml('Hello');
    $mail->assertSeeInHtml('Need help with &lt;b&gt;billing&lt;/b&gt;.', false);
    $mail->assertSeeInText($customer->email);
});
