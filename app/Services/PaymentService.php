<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Exceptions\BillingException;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Record a payment: full, partial, or one of many (BR-006).
     *
     * The invoice status follows automatically; settling the balance
     * moves the parent request to paid.
     *
     * @throws BillingException
     */
    public function pay(Invoice $invoice, float $amount, PaymentMethod $method, User $actor, ?string $reference = null): Payment
    {
        if (! $invoice->acceptsPayments()) {
            throw new BillingException("Invoice {$invoice->number} cannot receive payments in status '{$invoice->status->value}'.");
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new BillingException('Payment amount must be positive.');
        }

        if ($amount > $invoice->remaining()) {
            throw new BillingException("Payment of {$amount} EGP exceeds the remaining balance of {$invoice->remaining()} EGP.");
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $actor, $reference): Payment {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'received_by' => $actor->id,
                'paid_at' => now(),
            ]);

            $invoice->update(['paid_amount' => round((float) $invoice->paid_amount + $amount, 2)]);
            $invoice->refresh();

            if ($invoice->isPaid()) {
                $invoice->update(['status' => InvoiceStatus::Paid]);

                $this->transitions->transition(
                    $invoice->request->refresh(),
                    RequestStatus::Paid,
                    $actor,
                    "Invoice {$invoice->number} settled in full."
                );
            } else {
                $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);
            }

            AuditLog::record($actor, 'payment.received', $invoice->refresh(), [
                'amount' => $amount,
                'method' => $method->value,
                'remaining' => $invoice->remaining(),
            ]);

            $invoice->user->notify(new PaymentReceived($invoice->refresh(), $amount));

            return $payment;
        });
    }
}
