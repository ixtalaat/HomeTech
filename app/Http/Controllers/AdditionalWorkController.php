<?php

namespace App\Http\Controllers;

use App\Exceptions\AdditionalWorkException;
use App\Http\Requests\DecideAdditionalWorkRequest;
use App\Models\AdditionalWork;
use App\Services\AdditionalWorkService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class AdditionalWorkController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AdditionalWorkService $additionalWork) {}

    /**
     * Approve the additional work.
     */
    public function approve(DecideAdditionalWorkRequest $request, AdditionalWork $additionalWork): RedirectResponse
    {
        try {
            $this->additionalWork->decide($additionalWork, $request->user(), true);
        } catch (AdditionalWorkException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('requests.show', $additionalWork->workOrder->maintenance_request_id)
            ->with('success', __('Additional work approved. The technician can now proceed.'));
    }

    /**
     * Reject the additional work.
     */
    public function reject(DecideAdditionalWorkRequest $request, AdditionalWork $additionalWork): RedirectResponse
    {
        try {
            $this->additionalWork->decide($additionalWork, $request->user(), false);
        } catch (AdditionalWorkException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('requests.show', $additionalWork->workOrder->maintenance_request_id)
            ->with('success', __('Additional work rejected. It will not be billed.'));
    }
}
