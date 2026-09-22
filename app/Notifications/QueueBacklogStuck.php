<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QueueBacklogStuck extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * Deliberately synchronous: this alert must land even when queue
     * workers are down, so it never implements ShouldQueue.
     */
    public function __construct(
        public int $pending,
        public int $oldestMinutes
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
            'type' => 'queue_backlog_stuck',
            'pending' => $this->pending,
            'oldest_minutes' => $this->oldestMinutes,
            'message_key' => 'notifications.queue_backlog_stuck',
            'message_params' => ['count' => $this->pending, 'minutes' => $this->oldestMinutes],
            'message' => "Queue backlog: {$this->pending} jobs pending, oldest waiting {$this->oldestMinutes} minutes.",
        ];
    }
}
