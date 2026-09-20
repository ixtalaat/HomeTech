<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\MaintenanceRequest;
use App\Models\User;

class RequestReviewService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Approve a reviewable request, optionally adjusting the appointment and note.
     *
     * @param  array{admin_note?: ?string, preferred_date?: ?string, preferred_time?: ?string}  $attributes
     */
    public function approve(MaintenanceRequest $request, User $reviewer, array $attributes): MaintenanceRequest
    {
        if (! empty($attributes['preferred_date']) || ! empty($attributes['preferred_time'])) {
            $request->update([
                'preferred_date' => $attributes['preferred_date'] ?? $request->preferred_date,
                'preferred_time' => $attributes['preferred_time'] ?? $request->preferred_time,
            ]);
        }

        if (! empty($attributes['admin_note'])) {
            $request->update(['admin_note' => $attributes['admin_note']]);
        }

        $this->transitions->transition(
            $request->refresh(),
            RequestStatus::Approved,
            $reviewer,
            $attributes['admin_note'] ?? null
        );

        $request->update(['reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

        return $request->refresh();
    }

    /**
     * Reject a reviewable request with a required reason.
     */
    public function reject(MaintenanceRequest $request, User $reviewer, string $reason): MaintenanceRequest
    {
        $request->update([
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->transitions->transition($request->refresh(), RequestStatus::Rejected, $reviewer, $reason);

        return $request->refresh();
    }

    /**
     * Request more information from the customer.
     */
    public function requestInfo(MaintenanceRequest $request, User $reviewer, string $note): MaintenanceRequest
    {
        $request->update([
            'admin_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->transitions->transition($request->refresh(), RequestStatus::InfoRequested, $reviewer, $note);

        return $request->refresh();
    }

    /**
     * Change the preferred appointment of a reviewable request.
     *
     * @param  array{preferred_date: string, preferred_time: string}  $attributes
     */
    public function updateAppointment(MaintenanceRequest $request, array $attributes): MaintenanceRequest
    {
        $request->update($attributes);

        return $request->refresh();
    }
}
