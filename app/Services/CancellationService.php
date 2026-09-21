<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BillingException;
use App\Models\AuditLog;
use App\Models\Cancellation;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\JobCancelled;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancellationService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Cancel a request per the configurable policy (BR-008).
     *
     * Free when the appointment is beyond the free window, a percentage
     * fee of the base estimate when inside it, and refused entirely once
     * the technician has started the job.
     *
     * @throws BillingException
     */
    public function cancel(MaintenanceRequest $request, User $actor, string $reason): Cancellation
    {
        if ($request->cancellation !== null) {
            throw new BillingException("Request #{$request->id} is already cancelled.");
        }

        if (! in_array($request->status, $this->cancellableStatuses(), true)) {
            throw new BillingException("Request #{$request->id} cannot be cancelled from status '{$request->status->value}'.");
        }

        $fee = $this->feeFor($request);

        return DB::transaction(function () use ($request, $actor, $reason, $fee): Cancellation {
            $cancellation = Cancellation::create([
                'maintenance_request_id' => $request->id,
                'reason' => $reason,
                'fee' => $fee,
                'cancelled_by' => $actor->id,
            ]);

            if ($request->appointment !== null && ! $request->appointment->isCancelled()) {
                $request->appointment->update(['status' => AppointmentStatus::Cancelled]);
            }

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::Cancelled,
                $actor,
                $fee > 0 ? "Cancelled with a fee of {$fee} EGP: {$reason}" : "Cancelled without fee: {$reason}"
            );

            AuditLog::record($actor, 'request.cancelled', $request->refresh(), [
                'fee' => $fee,
            ], $reason);

            if ($request->technician !== null && $request->technician->user !== null) {
                $request->technician->user->notify(new JobCancelled($request->refresh(), $reason));
            }

            return $cancellation;
        });
    }

    /**
     * Preview the fee without cancelling.
     */
    public function previewFee(MaintenanceRequest $request): float
    {
        return $this->feeFor($request);
    }

    /**
     * Compute the fee for the request.
     */
    private function feeFor(MaintenanceRequest $request): float
    {
        $appointment = $request->appointment;

        if ($appointment === null) {
            return 0.0;
        }

        $start = Carbon::parse($appointment->date->format('Y-m-d').' '.$appointment->start_time);
        $freeHours = (int) config('billing.free_cancellation_hours', 24);

        if (now()->diffInHours($start, false) >= $freeHours) {
            return 0.0;
        }

        $percent = (float) config('billing.late_cancellation_fee_percent', 10);

        return round((float) $request->service->base_price * $percent / 100, 2);
    }

    /**
     * Statuses a request can still be cancelled from (BR-008).
     *
     * @return array<int, RequestStatus>
     */
    private function cancellableStatuses(): array
    {
        return [
            RequestStatus::PendingReview,
            RequestStatus::InfoRequested,
            RequestStatus::Approved,
            RequestStatus::TechnicianAssigned,
            RequestStatus::Scheduled,
            RequestStatus::TechnicianOnWay,
        ];
    }
}
