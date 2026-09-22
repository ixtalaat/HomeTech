<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SendSupportMessageRequest;
use App\Mail\SupportMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Show the contact-support form.
     */
    public function create(): View
    {
        return view('support.create');
    }

    /**
     * Send the user's message to the support inbox by mail.
     */
    public function store(SendSupportMessageRequest $request): RedirectResponse
    {
        $recipient = config('support.email')
            ?? User::whereIn('role', [UserRole::Admin, UserRole::Manager])->orderBy('id')->value('email');

        if ($recipient === null) {
            return back()
                ->withInput()
                ->with('error', __('Support is currently unavailable. Please try again later.'));
        }

        Mail::to($recipient)->send(new SupportMessage(
            $request->user(),
            $request->string('subject')->trim()->toString(),
            $request->string('message')->trim()->toString(),
        ));

        return redirect()
            ->route('support.create')
            ->with('success', __('Your message has been sent. Our team will reply soon.'));
    }
}
