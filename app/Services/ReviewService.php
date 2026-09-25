<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\ReviewException;
use App\Models\MaintenanceRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Submit a customer review for a finished job (BR-009).
     *
     * @throws ReviewException
     */
    public function submit(MaintenanceRequest $request, User $customer, int $rating, ?string $comment = null): Review
    {
        if ($request->user_id !== $customer->id) {
            throw new ReviewException(__('Only the customer who owns this job can review it.'));
        }

        if ($customer->role !== UserRole::Customer) {
            throw new ReviewException(__('Only customers can submit reviews.'));
        }

        if (! in_array($request->status, $this->reviewableStatuses(), true)) {
            throw new ReviewException("Reviews can only be submitted for finished jobs (request is '{$request->status->value}').");
        }

        if ($rating < 1 || $rating > 5) {
            throw new ReviewException(__('Rating must be between 1 and 5 stars.'));
        }

        if ($request->review !== null) {
            throw new ReviewException(__('This job has already been reviewed.'));
        }

        return DB::transaction(function () use ($request, $customer, $rating, $comment): Review {
            return Review::create([
                'maintenance_request_id' => $request->id,
                'user_id' => $customer->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        });
    }

    /**
     * Latest top-rated reviews with a comment, for public testimonials.
     *
     * @return Collection<int, Review>
     */
    public function spotlight(int $limit = 3): Collection
    {
        return Review::highlyRated()
            ->whereNotNull('comment')
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Statuses a job can be reviewed from (BR-009).
     *
     * @return array<int, RequestStatus>
     */
    private function reviewableStatuses(): array
    {
        return [
            RequestStatus::Completed,
            RequestStatus::Invoiced,
            RequestStatus::Paid,
            RequestStatus::Closed,
        ];
    }
}
