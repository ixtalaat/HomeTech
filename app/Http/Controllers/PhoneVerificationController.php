<?php

namespace App\Http\Controllers;

use App\Exceptions\PhoneVerificationException;
use App\Http\Requests\VerifyPhoneCodeRequest;
use App\Services\PhoneVerificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PhoneVerificationService $verifications) {}

    /**
     * Send a WhatsApp verification code to the user's phone.
     */
    public function send(Request $request): RedirectResponse
    {
        try {
            $this->verifications->sendCode($request->user());
        } catch (PhoneVerificationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Verification code sent via WhatsApp.'));
    }

    /**
     * Verify the submitted code.
     */
    public function verify(VerifyPhoneCodeRequest $request): RedirectResponse
    {
        try {
            $this->verifications->verifyCode($request->user(), $request->validated('code'));
        } catch (PhoneVerificationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Phone number verified successfully.'));
    }
}
