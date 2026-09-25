<?php

namespace App\Notifications;

use App\Models\AdditionalWork;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdditionalWorkRequiresApproval extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public AdditionalWork $additionalWork) {}

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

        return [
            'type' => 'additional_work_approval',
            'additional_work_id' => $this->additionalWork->id,
            'maintenance_request_id' => $request->id,
            'description' => $this->additionalWork->description,
            'cost' => (float) $this->additionalWork->cost,
            'message_key' => 'notifications.additional_work_approval',
            'message_params' => ['description' => $this->additionalWork->description, 'cost' => (float) $this->additionalWork->cost, 'id' => $request->id],
            'message' => "Approval needed: {$this->additionalWork->description} ({$this->additionalWork->cost} SAR) for request #{$request->id}.",
        ];
    }
}
