<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
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
     * Staff-recorded and Stripe-verified payments confirm immediately;
     * customer-recorded cash/bank payments await staff confirmation and
     * do not move the balance until confirmed.
     *
     * @throws BillingException
     */
    public function pay(Invoice $invoice, float $amount, PaymentMethod $method, User $actor, ?string $reference = null, bool $confirmed = false): Payment
    {
        if (! $invoice->acceptsPayments()) {
            throw new BillingException("Invoice {$invoice->number} cannot receive payments in status '{$invoice->status->value}'.");
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new BillingException(__('Payment amount must be positive.'));
        }

        if ($amount > $invoice->remaining()) {
            throw new BillingException("Payment of {$amount} EGP exceeds the remaining balance of {$invoice->remaining()} EGP.");
        }

        $recorded = round((float) $invoice->payments()->sum('amount'), 2);

        if (round($amount + $recorded, 2) > (float) $invoice->total) {
            throw new BillingException("This invoice already has payments recorded covering {$recorded} EGP of {$invoice->total} EGP.");
        }

        $confirmed = $confirmed || $this->isStaff($actor);

        return DB::transaction(function () use ($invoice, $amount, $method, $actor, $reference, $confirmed): Payment {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'received_by' => $actor->id,
                'paid_at' => now(),
                'confirmed_at' => $confirmed ? now() : null,
                'confirmed_by' => $confirmed ? $actor->id : null,
            ]);

            $this->refreshInvoiceBalance($invoice->refresh(), $actor);

            AuditLog::record($actor, 'payment.received', $invoice->refresh(), [
                'amount' => $amount,
                'method' => $method->value,
                'confirmed' => $confirmed,
                'remaining' => $invoice->remaining(),
            ]);

            if ($confirmed) {
                $invoice->user->notify(new PaymentReceived($invoice->refresh(), $amount));
            }

            return $payment;
        });
    }

    /**
     * Confirm a pending customer-recorded payment (staff only).
     *
     * @throws BillingException
     */
    public function confirm(Payment $payment, User $actor): Payment
    {
        if (! $this->isStaff($actor)) {
            throw new BillingException(__('Only staff can confirm payments.'));
        }

        if ($payment->confirmed_at !== null) {
            throw new BillingException(__('This payment is already confirmed.'));
        }

        return DB::transaction(function () use ($payment, $actor): Payment {
            $payment->update([
                'confirmed_at' => now(),
                'confirmed_by' => $actor->id,
            ]);

            $this->refreshInvoiceBalance($payment->invoice->refresh(), $actor);

            AuditLog::record($actor, 'payment.confirmed', $payment->invoice->refresh(), [
                'amount' => (float) $payment->amount,
            ]);

            $payment->invoice->user->notify(new PaymentReceived($payment->invoice->refresh(), (float) $payment->amount));

            return $payment->refresh();
        });
    }

    /**
     * Recompute paid_amount from confirmed payments and follow the status.
     */
    private function refreshInvoiceBalance(Invoice $invoice, User $actor): void
    {
        $confirmed = round((float) $invoice->payments()->whereNotNull('confirmed_at')->sum('amount'), 2);

        $invoice->update(['paid_amount' => $confirmed]);
        $invoice->refresh();

        if ($invoice->isPaid()) {
            $invoice->update(['status' => InvoiceStatus::Paid]);

            $request = $invoice->request->refresh();

            if ($request->status === RequestStatus::Invoiced) {
                $this->transitions->transition(
                    $request,
                    RequestStatus::Paid,
                    $actor,
                    "Invoice {$invoice->number} settled in full."
                );
            }
        } elseif ($confirmed > 0) {
            $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);
        }
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $actor): bool
    {
        return in_array($actor->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
