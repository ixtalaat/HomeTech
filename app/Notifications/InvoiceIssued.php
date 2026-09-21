<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceIssued extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Invoice $invoice) {}

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
            'type' => 'invoice_issued',
            'invoice_id' => $this->invoice->id,
            'maintenance_request_id' => $this->invoice->maintenance_request_id,
            'message' => "Invoice {$this->invoice->number} ({$this->invoice->total} EGP) was issued for your request #{$this->invoice->maintenance_request_id}.",
        ];
    }
}
