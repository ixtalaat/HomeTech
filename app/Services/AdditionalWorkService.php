<?php

namespace App\Services;

use App\Enums\AdditionalWorkStatus;
use App\Enums\RequestStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\AdditionalWorkException;
use App\Models\AdditionalWork;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\AdditionalWorkDecided;
use App\Notifications\AdditionalWorkRequiresApproval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdditionalWorkService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Request additional work during an in-progress visit (BR-005).
     *
     * Moves the parent request to waiting_customer_approval and notifies
     * the customer — atomically, so extras are never silent.
     *
     * @throws AdditionalWorkException
     */
    public function request(WorkOrder $workOrder, User $technician, string $description, float $cost): AdditionalWork
    {
        if ($workOrder->status !== WorkOrderStatus::InProgress) {
            throw new AdditionalWorkException('Additional work can only be requested while the job is in progress.');
        }

        if ($cost < 0) {
            throw new AdditionalWorkException('Additional work cost cannot be negative.');
        }

        return DB::transaction(function () use ($workOrder, $technician, $description, $cost): AdditionalWork {
            $item = AdditionalWork::create([
                'work_order_id' => $workOrder->id,
                'description' => $description,
                'cost' => $cost,
                'status' => AdditionalWorkStatus::PendingApproval,
                'requested_by' => $technician->id,
            ]);

            $this->transitions->transition(
                $workOrder->request->refresh(),
                RequestStatus::WaitingCustomerApproval,
                $technician,
                "Additional work requested: {$description}."
            );

            $workOrder->request->user->notify(new AdditionalWorkRequiresApproval($item->refresh()));

            return $item->refresh();
        });
    }

    /**
     * Record the customer decision (approve or reject) with timestamp.
     *
     * Returns the request to in_progress either way; rejected work is
     * excluded from billing by the billable scope (BR-005).
     *
     * @throws AdditionalWorkException
     */
    public function decide(AdditionalWork $item, User $customer, bool $approve): AdditionalWork
    {
        if (! $item->isPending()) {
            throw new AdditionalWorkException('This additional work has already been decided.');
        }

        $ownerId = $item->workOrder->request->user_id;

        if ($ownerId !== $customer->id) {
            throw new AdditionalWorkException('Only the customer who owns this job can decide on additional work.');
        }

        if ($item->requested_by === $customer->id) {
            throw new AdditionalWorkException('Technicians cannot approve their own additional work.');
        }

        return DB::transaction(function () use ($item, $customer, $approve): AdditionalWork {
            $item->update([
                'status' => $approve ? AdditionalWorkStatus::Approved : AdditionalWorkStatus::Rejected,
                'decided_by' => $customer->id,
                'decided_at' => now(),
            ]);

            $this->transitions->transition(
                $item->workOrder->request->refresh(),
                RequestStatus::InProgress,
                $customer,
                $approve ? 'Customer approved the additional work.' : 'Customer rejected the additional work.'
            );

            $item->requester->notify(new AdditionalWorkDecided($item->refresh(), $approve));

            return $item->refresh();
        });
    }

    /**
     * Mark approved work as performed by the owning technician.
     *
     * @throws AdditionalWorkException
     */
    public function markCompleted(AdditionalWork $item, User $technician): AdditionalWork
    {
        if ($item->status !== AdditionalWorkStatus::Approved) {
            throw new AdditionalWorkException('Only approved additional work can be marked as performed.');
        }

        if ($item->workOrder->technician === null || $item->workOrder->technician->user_id !== $technician->id) {
            throw new AdditionalWorkException('Only the assigned technician can mark this work as performed.');
        }

        $item->update([
            'status' => AdditionalWorkStatus::Completed,
            'completed_at' => now(),
        ]);

        return $item->refresh();
    }

    /**
     * Get the billable items for a work order (approved or performed only).
     *
     * @return Collection<int, AdditionalWork>
     */
    public function billableFor(WorkOrder $workOrder): Collection
    {
        return $workOrder->additionalWorkItems()->billable()->get();
    }
}
