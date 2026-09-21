<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice {$this->invoice->number} issued")
            ->line("Invoice {$this->invoice->number} for {$this->invoice->total} EGP has been issued for your maintenance request #{$this->invoice->maintenance_request_id}.")
            ->action('View Invoice', route('invoices.show', $this->invoice))
            ->line('Thank you for using HomeTech!');
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
            'message_key' => 'notifications.invoice_issued',
            'message_params' => ['number' => $this->invoice->number, 'total' => $this->invoice->total, 'id' => $this->invoice->maintenance_request_id],
            'message' => "Invoice {$this->invoice->number} ({$this->invoice->total} EGP) was issued for your request #{$this->invoice->maintenance_request_id}.",
        ];
    }
}
