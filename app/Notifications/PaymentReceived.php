<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public float $amount
    ) {}

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
            ->subject("Payment received for invoice {$this->invoice->number}")
            ->line("We received your payment of {$this->amount} SAR for invoice {$this->invoice->number}.")
            ->line("Remaining balance: {$this->invoice->remaining()} SAR.")
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
            'type' => 'payment_received',
            'invoice_id' => $this->invoice->id,
            'maintenance_request_id' => $this->invoice->maintenance_request_id,
            'message_key' => 'notifications.payment_received',
            'message_params' => ['amount' => $this->amount, 'number' => $this->invoice->number, 'remaining' => $this->invoice->remaining()],
            'message' => "Payment of {$this->amount} SAR received for invoice {$this->invoice->number}. Remaining: {$this->invoice->remaining()} SAR.",
        ];
    }
}
