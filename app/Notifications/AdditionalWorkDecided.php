<?php

namespace App\Notifications;

use App\Models\AdditionalWork;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdditionalWorkDecided extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AdditionalWork $additionalWork,
        public bool $approved
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
        $request = $this->additionalWork->workOrder->request;
        $verdict = $this->approved ? 'approved' : 'rejected';

        return [
            'type' => 'additional_work_decided',
            'additional_work_id' => $this->additionalWork->id,
            'maintenance_request_id' => $request->id,
            'approved' => $this->approved,
            'message' => "The customer {$verdict} the additional work '{$this->additionalWork->description}' for request #{$request->id}.",
        ];
    }
}
