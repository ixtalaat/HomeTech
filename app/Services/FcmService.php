<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(
        private ?string $credentialsPath = null,
        private ?string $projectId = null,
    ) {
        $this->credentialsPath ??= (string) config('firebase.credentials');

        // A relative path in .env resolves against the app root so sending
        // works from any working directory (web workers, queue, scheduler).
        if (! self::isAbsolutePath($this->credentialsPath)) {
            $this->credentialsPath = base_path($this->credentialsPath);
        }

        $configuredProjectId = config('firebase.project_id');
        $this->projectId ??= is_string($configuredProjectId) && $configuredProjectId !== ''
            ? $configuredProjectId
            : $this->projectIdFromCredentials();
    }

    public function isConfigured(): bool
    {
        return $this->projectId !== null && $this->projectId !== '' && is_file($this->credentialsPath);
    }

    public function projectId(): ?string
    {
        return $this->projectId !== '' ? $this->projectId : null;
    }

    /**
     * Send a push notification to every registered device of the user.
     *
     * Stale tokens reported by FCM are deleted. Never throws: delivery
     * problems are counted and logged instead.
     *
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = $user->fcmTokens()->pluck('token')->all();

        if ($tokens === [] || ! $this->isConfigured()) {
            return ['sent' => 0, 'failed' => 0];
        }

        try {
            $accessToken = $this->accessToken();
        } catch (\Throwable $exception) {
            Log::warning('FCM access token failed.', ['error' => $exception->getMessage()]);

            return ['sent' => 0, 'failed' => count($tokens)];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)->post($this->endpoint(), [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $this->stringData($data),
                    ],
                ]);
            } catch (\Throwable $exception) {
                Log::warning('FCM send failed.', ['error' => $exception->getMessage()]);
                $failed++;

                continue;
            }

            if ($response->successful()) {
                $sent++;

                continue;
            }

            $failed++;

            if ($this->isDeadToken($response)) {
                $user->fcmTokens()->where('token', $token)->delete();
            } else {
                Log::warning('FCM message rejected.', [
                    'http_status' => $response->status(),
                    'error' => $response->json('error.status'),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Validate every stored token without delivering anything.
     *
     * Uses FCM's validate_only flag: registry-dead tokens are deleted so
     * future sends skip them. Never throws.
     *
     * @return array{checked: int, pruned: int}
     */
    public function pruneStaleTokens(): array
    {
        if (! $this->isConfigured()) {
            return ['checked' => 0, 'pruned' => 0];
        }

        try {
            $accessToken = $this->accessToken();
        } catch (\Throwable $exception) {
            Log::warning('FCM access token failed.', ['error' => $exception->getMessage()]);

            return ['checked' => 0, 'pruned' => 0];
        }

        $checked = 0;
        $pruned = 0;

        foreach (FcmToken::query()->orderBy('id')->pluck('token', 'id') as $id => $token) {
            $checked++;

            try {
                $response = Http::withToken($accessToken)->post($this->endpoint(), [
                    'validate_only' => true,
                    'message' => ['token' => $token],
                ]);
            } catch (\Throwable $exception) {
                Log::warning('FCM validation failed.', ['error' => $exception->getMessage()]);

                continue;
            }

            if (! $response->successful() && $this->isDeadToken($response)) {
                FcmToken::whereKey($id)->delete();
                $pruned++;
            }
        }

        return ['checked' => $checked, 'pruned' => $pruned];
    }

    protected function endpoint(): string
    {
        return "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    protected function accessToken(): string
    {
        return Cache::remember('fcm-access-token', 50 * 60, function (): string {
            $credentials = new ServiceAccountCredentials(
                self::MESSAGING_SCOPE,
                json_decode((string) file_get_contents((string) $this->credentialsPath), true)
            );

            $token = $credentials->fetchAuthToken();

            return $token['access_token'];
        });
    }

    /**
     * FCM data values must be strings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringData(array $data): array
    {
        $strings = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $strings[(string) $key] = is_scalar($value) ? (string) $value : (string) json_encode($value);
        }

        return $strings;
    }

    private function isDeadToken(Response $response): bool
    {
        return in_array($response->json('error.status'), ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true);
    }

    private function projectIdFromCredentials(): ?string
    {
        if (! is_file($this->credentialsPath)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents((string) $this->credentialsPath), true);

            return is_array($decoded) && isset($decoded['project_id']) ? (string) $decoded['project_id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('#^(?:[A-Z]:[\\\\/]|\\\\\\\\|/)#i', $path);
    }
}
