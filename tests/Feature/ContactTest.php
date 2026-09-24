<?php

use App\Enums\UserRole;
use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('shows the public about page with live stats', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('A maintenance company built around your home.', false)
        ->assertSee('Active services', false);
});

it('shows the public contact page with a form', function () {
    $this->get(route('contact.create'))
        ->assertOk()
        ->assertSee('Questions? Write to us.', false);
});

it('sends guest messages to the support inbox', function () {
    Mail::fake();
    config(['support.email' => null]);

    $admin = User::factory()->create(['role' => UserRole::Admin, 'email' => 'admin@hometech.test']);

    $this->post(route('contact.store'), [
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'subject' => 'Question about pricing',
        'message' => 'How much does AC maintenance cost?',
    ])->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo($admin->email));
});

it('validates the contact form', function () {
    $this->post(route('contact.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'subject' => '',
        'message' => '',
    ])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
});

it('renders both pages in Arabic', function () {
    $this->withSession(['locale' => 'ar'])->get(route('about'))
        ->assertOk()
        ->assertSee('شركة صيانة مبنية حول منزلك', false);

    $this->withSession(['locale' => 'ar'])->get(route('contact.create'))
        ->assertOk()
        ->assertSee('أسئلة؟ راسلنا', false);
});
