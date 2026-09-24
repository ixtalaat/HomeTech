<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Show the public contact page.
     */
    public function create(): View
    {
        return view('contact');
    }

    /**
     * Send a guest message to the support inbox.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $recipient = config('support.email')
            ?? User::whereIn('role', [UserRole::Admin, UserRole::Manager])->orderBy('id')->value('email');

        if ($recipient === null) {
            return back()
                ->withInput()
                ->with('error', __('Support is currently unavailable. Please try again later.'));
        }

        Mail::to($recipient)->send(new ContactMessage(
            trim($validated['name']),
            trim($validated['email']),
            trim($validated['subject']),
            trim($validated['message']),
        ));

        return back()->with('success', __('Your message has been sent. Our team will reply soon.'));
    }
}
