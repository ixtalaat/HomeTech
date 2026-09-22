<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RescheduleNeeded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MaintenanceRequest $request,
        public string $reason
    ) {}

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
        return [
            'type' => 'reschedule_needed',
            'maintenance_request_id' => $this->request->id,
            'message_key' => 'notifications.reschedule_needed',
            'message_params' => ['id' => $this->request->id, 'reason' => $this->reason],
            'message' => "No technician is available for request #{$this->request->id}: {$this->reason} Please choose another appointment time.",
        ];
    }
}
