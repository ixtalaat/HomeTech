<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class ForwardDatabaseNotificationsToFcm
{
    public function __construct(private FcmService $push) {}

    /**
     * Mirror an in-app notification to the user's push devices (best effort).
     */
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        if (! $notifiable instanceof User) {
            return;
        }

        try {
            $data = $event->notification->toArray($notifiable);

            $params = $data['message_params'] ?? null;

            $body = isset($data['message_key']) && is_string($data['message_key'])
                ? __($data['message_key'], is_array($params) ? $params : [])
                : (string) ($data['message'] ?? __('notifications.fallback'));

            $payload = ['type' => (string) ($data['type'] ?? 'update'), 'url' => '/notifications'];

            foreach (['maintenance_request_id', 'invoice_id'] as $key) {
                if (isset($data[$key])) {
                    $payload[$key] = (string) $data[$key];
                }
            }

            $this->push->sendToUser($notifiable, (string) config('app.name'), $body, $payload);
        } catch (\Throwable $exception) {
            Log::warning('Forwarding a notification to FCM failed.', ['error' => $exception->getMessage()]);
        }
    }
}
