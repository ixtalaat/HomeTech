<?php

namespace App\Http\Controllers;

use App\Exceptions\ReviewException;
use App\Http\Requests\StoreReviewRequest;
use App\Models\MaintenanceRequest;
use App\Services\ReviewService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ReviewService $reviews) {}

    /**
     * Submit a review for a finished job.
     */
    public function store(StoreReviewRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless($maintenanceRequest->isOwnedBy($request->user()), 404);

        try {
            $validated = $request->validated();

            $this->reviews->submit(
                $maintenanceRequest,
                $request->user(),
                (int) $validated['rating'],
                $validated['comment'] ?? null
            );
        } catch (ReviewException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('requests.show', $maintenanceRequest)
            ->with('success', __('Thank you for your feedback!'));
    }
}
