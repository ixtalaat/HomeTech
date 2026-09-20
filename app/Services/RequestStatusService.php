<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestStatusService
{
    /**
     * Allowed status transitions.
     *
     * Later epics (assignment, scheduling, work orders, billing) unlock
     * their edges here as those features are implemented.
     *
     * @var array<string, array<int, RequestStatus>>
     */
    private const TRANSITIONS = [
        'pending_review' => [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::InfoRequested, RequestStatus::Cancelled],
        'info_requested' => [RequestStatus::PendingReview, RequestStatus::Rejected, RequestStatus::Cancelled],
    ];

    /**
     * Determine whether a transition from the given status is allowed.
     */
    public function canTransition(RequestStatus $from, RequestStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /**
     * Transition the request to a new status, recording history.
     *
     * @throws InvalidStatusTransitionException
     */
    public function transition(MaintenanceRequest $request, RequestStatus $to, ?User $actor = null, ?string $reason = null): MaintenanceRequest
    {
        $from = $request->status;

        if ($from === $to) {
            return $request;
        }

        if (! $this->canTransition($from, $to)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition maintenance request #{$request->id} from '{$from->value}' to '{$to->value}'."
            );
        }

        return DB::transaction(function () use ($request, $from, $to, $actor, $reason): MaintenanceRequest {
            $request->update(['status' => $to]);

            $request->statusHistories()->create([
                'from_status' => $from->value,
                'status' => $to->value,
                'changed_by' => $actor?->id,
                'reason' => $reason,
            ]);

            return $request->refresh();
        });
    }
}
