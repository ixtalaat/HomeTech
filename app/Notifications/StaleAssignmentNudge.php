<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StaleAssignmentNudge extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MaintenanceRequest $request) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $days = $this->request->updated_at->diffInDays(now());

        return [
            'type' => 'stale_assignment_nudge',
            'maintenance_request_id' => $this->request->id,
            'message_key' => 'notifications.stale_assignment_nudge',
            'message_params' => ['id' => $this->request->id, 'days' => $days],
            'message' => "Request #{$this->request->id} was approved {$days} days ago and is still unassigned.",
        ];
    }
}
