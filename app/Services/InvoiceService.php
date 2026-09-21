<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BillingException;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\InvoiceIssued;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private PricingService $pricing,
        private RequestStatusService $transitions
    ) {}

    /**
     * Generate a draft invoice from a completed work order (BR-010).
     *
     * Every line references its source charge; rejected or pending
     * additional work is structurally unreachable.
     *
     * @throws BillingException
     */
    public function generate(WorkOrder $workOrder, User $actor): Invoice
    {
        $request = $workOrder->request;

        if ($workOrder->status->value !== 'completed' || $request->status !== RequestStatus::Completed) {
            throw new BillingException('Invoices can only be generated for completed jobs.');
        }

        if ($request->invoice !== null) {
            throw new BillingException("Request #{$request->id} already has invoice {$request->invoice->number}.");
        }

        return DB::transaction(function () use ($workOrder, $request, $actor): Invoice {
            $breakdown = $this->pricing->breakdown($workOrder->refresh());

            $invoice = Invoice::create([
                'number' => 'PENDING',
                'maintenance_request_id' => $request->id,
                'user_id' => $request->user_id,
                'subtotal' => $breakdown['subtotal'],
                'total' => $breakdown['subtotal'],
                'status' => InvoiceStatus::Draft,
                'created_by' => $actor->id,
            ]);

            $invoice->update(['number' => 'INV-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT)]);

            $this->buildItems($invoice->refresh(), $workOrder);

            AuditLog::record($actor, 'invoice.generated', $invoice, ['total' => $invoice->total]);

            return $invoice->refresh();
        });
    }

    /**
     * Issue a draft invoice, making it payable and the request invoiced.
     *
     * @throws BillingException
     */
    public function issue(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new BillingException("Only draft invoices can be issued ({$invoice->number} is {$invoice->status->value}).");
        }

        return DB::transaction(function () use ($invoice, $actor): Invoice {
            $invoice->update(['status' => InvoiceStatus::Issued, 'issued_at' => now()]);

            $this->transitions->transition(
                $invoice->request->refresh(),
                RequestStatus::Invoiced,
                $actor,
                "Invoice {$invoice->number} issued."
            );

            AuditLog::record($actor, 'invoice.issued', $invoice);

            $invoice->user->notify(new InvoiceIssued($invoice->refresh()));

            return $invoice->refresh();
        });
    }

    /**
     * Apply a discount, enforcing the non-negative total and manager gate.
     *
     * @throws BillingException
     */
    public function applyDiscount(Invoice $invoice, User $actor, DiscountType $type, float $value): Invoice
    {
        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true)) {
            throw new BillingException("Cannot discount a {$invoice->status->value} invoice through normal operations.");
        }

        $amount = $this->pricing->discountAmount((float) $invoice->subtotal, $type, $value, $actor);

        return DB::transaction(function () use ($invoice, $actor, $type, $value, $amount): Invoice {
            $invoice->update([
                'discount_type' => $type,
                'discount_value' => $value,
                'discount_amount' => $amount,
                'total' => round((float) $invoice->subtotal - $amount, 2),
            ]);

            AuditLog::record($actor, 'invoice.discounted', $invoice->refresh(), [
                'type' => $type->value,
                'value' => $value,
                'amount' => $amount,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Cancel an unpaid invoice.
     *
     * @throws BillingException
     */
    public function cancelInvoice(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new BillingException('Paid invoices cannot be cancelled through normal operations.');
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new BillingException('Invoices with recorded payments cannot be cancelled.');
        }

        $invoice->update(['status' => InvoiceStatus::Cancelled]);

        AuditLog::record($actor, 'invoice.cancelled', $invoice->refresh());

        return $invoice->refresh();
    }

    /**
     * Close a fully paid invoice and its request.
     *
     * @throws BillingException
     */
    public function markClosed(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Paid) {
            throw new BillingException('Only fully paid invoices can be closed.');
        }

        $this->transitions->transition(
            $invoice->request->refresh(),
            RequestStatus::Closed,
            $actor,
            "Invoice {$invoice->number} closed."
        );

        return $invoice->refresh();
    }

    /**
     * Build invoice lines from actual recorded charges (BR-010).
     */
    private function buildItems(Invoice $invoice, WorkOrder $workOrder): void
    {
        $service = $workOrder->request->service;

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Service,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $service->base_price,
            'total' => $service->base_price,
            'source_type' => $service->getMorphClass(),
            'source_id' => $service->getKey(),
        ]);

        foreach ($workOrder->laborItems as $labor) {
            $invoice->items()->create([
                'item_type' => InvoiceItemType::Labor,
                'description' => $labor->description,
                'quantity' => 1,
                'unit_price' => $labor->cost,
                'total' => $labor->cost,
                'source_type' => $labor->getMorphClass(),
                'source_id' => $labor->getKey(),
            ]);
        }

        foreach ($workOrder->materialUsages as $usage) {
            $invoice->items()->create([
                'item_type' => InvoiceItemType::Material,
                'description' => "{$usage->item->name} × {$usage->quantity}",
                'quantity' => $usage->quantity,
                'unit_price' => $usage->unit_cost,
                'total' => $usage->extendedCost(),
                'source_type' => $usage->getMorphClass(),
                'source_id' => $usage->getKey(),
            ]);
        }

        foreach ($workOrder->additionalWorkItems()->billable()->get() as $extra) {
            $invoice->items()->create([
                'item_type' => InvoiceItemType::Additional,
                'description' => $extra->description,
                'quantity' => 1,
                'unit_price' => $extra->cost,
                'total' => $extra->cost,
                'source_type' => $extra->getMorphClass(),
                'source_id' => $extra->getKey(),
            ]);
        }
    }
}
