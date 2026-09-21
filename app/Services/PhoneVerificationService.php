<?php

namespace App\Services;

use App\Exceptions\PhoneVerificationException;
use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PhoneVerificationService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    /**
     * Issue a verification code to the user's phone via WhatsApp.
     *
     * Any previous pending code is replaced. Returns the verification
     * record (the plain code is only ever sent, never stored).
     *
     * @throws PhoneVerificationException
     */
    public function sendCode(User $user): PhoneVerification
    {
        if (empty($user->phone)) {
            throw new PhoneVerificationException(__('Add a phone number to your profile first.'));
        }

        if ($user->phone_verified_at !== null) {
            throw new PhoneVerificationException(__('This phone number is already verified.'));
        }

        $code = (string) random_int(100000, 999999);

        $verification = DB::transaction(function () use ($user, $code): PhoneVerification {
            PhoneVerification::where('user_id', $user->id)->delete();

            return PhoneVerification::create([
                'user_id' => $user->id,
                'phone' => $user->phone,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('whatsapp.code_ttl_minutes', 10)),
                'attempts' => 0,
            ]);
        });

        try {
            $this->whatsapp->send(
                $user->phone,
                "Your HomeTech verification code is: {$code}. It expires in ".config('whatsapp.code_ttl_minutes', 10).' minutes.'
            );
        } catch (\RuntimeException $exception) {
            throw new PhoneVerificationException($exception->getMessage());
        }

        return $verification;
    }

    /**
     * Check a submitted code: expiry, attempt limit, then hash match.
     *
     * @throws PhoneVerificationException
     */
    public function verifyCode(User $user, string $code): User
    {
        $verification = PhoneVerification::where('user_id', $user->id)->latest()->first();

        if ($verification === null) {
            throw new PhoneVerificationException(__('No verification code was requested. Send a new one first.'));
        }

        if ($verification->isExpired()) {
            $verification->delete();

            throw new PhoneVerificationException(__('The code expired. Send a new one.'));
        }

        if ($verification->attempts >= (int) config('whatsapp.code_max_attempts', 5)) {
            $verification->delete();

            throw new PhoneVerificationException(__('Too many wrong attempts. Send a new code.'));
        }

        $verification->increment('attempts');

        if (! Hash::check($code, $verification->code_hash)) {
            throw new PhoneVerificationException(__('Wrong code. Try again.'));
        }

        return DB::transaction(function () use ($user, $verification): User {
            $user->update(['phone_verified_at' => now()]);
            $verification->delete();

            return $user->refresh();
        });
    }
}
