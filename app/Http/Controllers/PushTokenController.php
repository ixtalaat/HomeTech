<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushTokenRequest;
use App\Models\FcmToken;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    public function __construct(private FcmService $push) {}

    /**
     * Public web-push configuration for the Firebase JS SDK.
     *
     * Only public client identifiers are exposed here; the private
     * service-account key never leaves the server. The project ID falls
     * back to the one inside the credentials file.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'apiKey' => config('firebase.web.api_key'),
            'authDomain' => config('firebase.web.auth_domain'),
            'projectId' => config('firebase.web.project_id') ?? $this->push->projectId(),
            'senderId' => config('firebase.web.sender_id'),
            'appId' => config('firebase.web.app_id'),
            'vapidKey' => config('firebase.vapid_key'),
        ]);
    }

    /**
     * Register (or refresh) a push device token for the user.
     *
     * Tokens are globally unique: re-subscribing from another account
     * moves the device to the currently signed-in user.
     */
    public function store(StorePushTokenRequest $request): JsonResponse
    {
        FcmToken::updateOrCreate(
            ['token' => $request->string('token')->toString()],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->input('platform'),
                'device_name' => $request->input('device_name'),
            ]
        );

        return response()->json(['saved' => true], 201);
    }

    /**
     * Remove a push device token (e.g. on logout or denied permission).
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $request->user()->fcmTokens()->where('token', $validated['token'])->delete();

        return response()->json(['deleted' => true]);
    }
}
