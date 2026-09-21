<?php

namespace App\Notifications;

use App\Models\DiscountApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiscountApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DiscountApproval $approval) {}

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
        $invoice = $this->approval->invoice;

        return [
            'type' => 'discount_approval_requested',
            'discount_approval_id' => $this->approval->id,
            'invoice_id' => $invoice->id,
            'message' => "Discount approval needed: {$this->approval->discount_value} {$this->approval->discount_type->value} on invoice {$invoice->number}.",
        ];
    }
}
