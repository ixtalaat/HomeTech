<?php

namespace App\Services;

use App\Enums\DiscountApprovalStatus;
use App\Enums\DiscountType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\DiscountApproval;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DiscountApprovalRequested;
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
     * Generate a draft fee invoice for a cancellation with a fee.
     *
     * Staff may cancel (waive) it like any unpaid invoice.
     *
     * @throws BillingException
     */
    public function generateForCancellation(Cancellation $cancellation, User $actor): Invoice
    {
        $request = $cancellation->request;

        if ((float) $cancellation->fee <= 0) {
            throw new BillingException('Only cancellations with a fee can be invoiced.');
        }

        if ($request->invoice !== null) {
            throw new BillingException("Request #{$request->id} already has invoice {$request->invoice->number}.");
        }

        return DB::transaction(function () use ($cancellation, $request, $actor): Invoice {
            $invoice = Invoice::create([
                'number' => 'PENDING',
                'maintenance_request_id' => $request->id,
                'user_id' => $request->user_id,
                'subtotal' => $cancellation->fee,
                'total' => $cancellation->fee,
                'status' => InvoiceStatus::Draft,
                'created_by' => $actor->id,
                'notes' => "Cancellation fee: {$cancellation->reason}",
            ]);

            $invoice->update(['number' => 'INV-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT)]);

            $invoice->items()->create([
                'item_type' => InvoiceItemType::Fee,
                'description' => "Late cancellation fee for request #{$request->id}",
                'quantity' => 1,
                'unit_price' => $cancellation->fee,
                'total' => $cancellation->fee,
                'source_type' => $cancellation->getMorphClass(),
                'source_id' => $cancellation->getKey(),
            ]);

            AuditLog::record($actor, 'invoice.generated', $invoice->refresh(), [
                'total' => $invoice->total,
                'cancellation_fee' => true,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Issue a draft invoice, making it payable and the request invoiced.
     *
     * Cancelled requests stay cancelled — only the invoice moves.
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

            $request = $invoice->request->refresh();

            if ($request->status === RequestStatus::Completed) {
                $this->transitions->transition(
                    $request,
                    RequestStatus::Invoiced,
                    $actor,
                    "Invoice {$invoice->number} issued."
                );
            }

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
        $this->guardDiscountable($invoice);

        $amount = $this->pricing->discountAmount((float) $invoice->subtotal, $type, $value, $actor);

        return DB::transaction(function () use ($invoice, $actor, $type, $value, $amount): Invoice {
            $this->writeDiscount($invoice, $actor, $type, $value, $amount);

            return $invoice->refresh();
        });
    }

    /**
     * Apply a discount directly, or queue it for manager approval.
     *
     * Returns 'applied' when the discount took effect immediately and
     * 'queued' when it was sent to managers.
     *
     * @throws BillingException
     */
    public function applyDiscountOrQueue(Invoice $invoice, User $actor, DiscountType $type, float $value): string
    {
        if ($this->needsManagerApproval($invoice, $type, $value) && $actor->role !== UserRole::Manager) {
            $this->requestDiscountApproval($invoice, $actor, $type, $value);

            return 'queued';
        }

        $this->applyDiscount($invoice, $actor, $type, $value);

        return 'applied';
    }

    /**
     * Determine whether the discount needs manager approval first.
     */
    public function needsManagerApproval(Invoice $invoice, DiscountType $type, float $value): bool
    {
        return $this->pricing->requiresManagerApproval($type, $value);
    }

    /**
     * Queue a high-value discount for manager approval and notify managers.
     *
     * @throws BillingException
     */
    public function requestDiscountApproval(Invoice $invoice, User $staff, DiscountType $type, float $value): DiscountApproval
    {
        $this->guardDiscountable($invoice);

        $this->pricing->computeAmount((float) $invoice->subtotal, $type, $value);

        return DB::transaction(function () use ($invoice, $staff, $type, $value): DiscountApproval {
            $approval = DiscountApproval::create([
                'invoice_id' => $invoice->id,
                'discount_type' => $type,
                'discount_value' => $value,
                'status' => DiscountApprovalStatus::Pending,
                'requested_by' => $staff->id,
            ]);

            User::where('role', UserRole::Manager)->where('is_active', true)->each(
                fn (User $manager): mixed => $manager->notify(new DiscountApprovalRequested($approval))
            );

            AuditLog::record($staff, 'discount.approval_requested', $invoice, [
                'type' => $type->value,
                'value' => $value,
            ]);

            return $approval;
        });
    }

    /**
     * Approve a queued discount (managers only) and apply it.
     *
     * @throws BillingException
     */
    public function approveDiscountRequest(DiscountApproval $approval, User $manager): Invoice
    {
        $this->guardManager($manager);

        if (! $approval->isPending()) {
            throw new BillingException('This discount request has already been decided.');
        }

        $invoice = $approval->invoice;
        $this->guardDiscountable($invoice);

        $type = $approval->discount_type;
        $value = (float) $approval->discount_value;
        $amount = $this->pricing->computeAmount((float) $invoice->subtotal, $type, $value);

        return DB::transaction(function () use ($approval, $manager, $invoice, $type, $value, $amount): Invoice {
            $approval->update([
                'status' => DiscountApprovalStatus::Approved,
                'decided_by' => $manager->id,
                'decided_at' => now(),
            ]);

            $this->writeDiscount($invoice, $manager, $type, $value, $amount);

            return $invoice->refresh();
        });
    }

    /**
     * Reject a queued discount (managers only).
     *
     * @throws BillingException
     */
    public function rejectDiscountRequest(DiscountApproval $approval, User $manager): DiscountApproval
    {
        $this->guardManager($manager);

        if (! $approval->isPending()) {
            throw new BillingException('This discount request has already been decided.');
        }

        $approval->update([
            'status' => DiscountApprovalStatus::Rejected,
            'decided_by' => $manager->id,
            'decided_at' => now(),
        ]);

        AuditLog::record($manager, 'discount.approval_rejected', $approval->invoice, [
            'type' => $approval->discount_type->value,
            'value' => (float) $approval->discount_value,
        ]);

        return $approval->refresh();
    }

    /**
     * Guard that the invoice can still be discounted.
     *
     * @throws BillingException
     */
    private function guardDiscountable(Invoice $invoice): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true)) {
            throw new BillingException("Cannot discount a {$invoice->status->value} invoice through normal operations.");
        }
    }

    /**
     * Guard that the actor is a manager.
     *
     * @throws BillingException
     */
    private function guardManager(User $actor): void
    {
        if ($actor->role !== UserRole::Manager) {
            throw new BillingException('Only managers can decide discount approvals.');
        }
    }

    /**
     * Write discount columns and audit the change.
     */
    private function writeDiscount(Invoice $invoice, User $actor, DiscountType $type, float $value, float $amount): void
    {
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
