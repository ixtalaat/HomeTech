<?php

namespace App\Http\Controllers\Branch;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\TechnicianAssignmentException;
use App\Http\Requests\Branch\AssignTechnicianRequest;
use App\Http\Requests\Branch\ReviewRequest;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Notifications\RescheduleNeeded;
use App\Services\BranchService;
use App\Services\MaintenanceRequestService;
use App\Services\RequestReviewService;
use App\Services\TechnicianAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends BaseController
{
    public function __construct(
        private MaintenanceRequestService $requests,
        private RequestReviewService $reviews,
        private TechnicianAssignmentService $assignments,
        private BranchService $branches
    ) {}

    /**
     * Display the branch maintenance requests.
     */
    public function index(Request $request): View
    {
        $branch = $this->managedBranch();

        $requests = $this->requests->paginateForAdmin($request->only(['search', 'status']), $branch);
        $statuses = RequestStatus::cases();

        return view('branch.requests.index', compact('branch', 'requests', 'statuses'));
    }

    /**
     * Display a branch maintenance request for review.
     */
    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        $maintenanceRequest->load(['user', 'service.category', 'address', 'technician.user', 'appointment']);

        $eligibleTechnicians = $this->assignments->eligibleWithSlotStatus($maintenanceRequest, $branch);

        return view('branch.requests.show', compact('branch', 'maintenanceRequest', 'eligibleTechnicians'));
    }

    /**
     * Approve a pending request, then auto-assign a technician.
     */
    public function approve(ReviewRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        try {
            $this->reviews->approve($maintenanceRequest, $request->user(), $request->validated());
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        try {
            $result = $this->assignments->autoAssign($maintenanceRequest->refresh(), $request->user());
        } catch (TechnicianAssignmentException $exception) {
            return $this->approvedResponse($maintenanceRequest, $exception->getMessage());
        }

        if ($result['technician'] === null) {
            if (in_array($result['code'], ['no_match', 'past_slot'], true)) {
                $maintenanceRequest->user->notify(new RescheduleNeeded($maintenanceRequest, (string) $result['reason']));
            }

            return $this->approvedResponse($maintenanceRequest, __('Automatic assignment skipped: :reason', ['reason' => $result['reason']]));
        }

        return $this->approvedResponse(
            $maintenanceRequest,
            __('Automatically assigned to :name.', ['name' => $result['technician']->user->name])
        );
    }

    /**
     * Reject a pending request with a required reason.
     */
    public function reject(ReviewRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        try {
            $this->reviews->reject($maintenanceRequest, $request->user(), $request->validated('rejection_reason'));
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('branch.requests.show', $maintenanceRequest)
            ->with('success', __('Maintenance request rejected.'));
    }

    /**
     * Request more information from the customer.
     */
    public function requestInfo(ReviewRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        try {
            $this->reviews->requestInfo($maintenanceRequest, $request->user(), $request->validated('admin_note'));
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('branch.requests.show', $maintenanceRequest)
            ->with('success', __('Information request sent to the customer.'));
    }

    /**
     * Assign an in-branch technician, booking the preferred slot.
     */
    public function assign(AssignTechnicianRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        $technician = Technician::with('user')->findOrFail($request->validated('technician_id'));

        abort_unless($technician->branch_id === $branch->id, 404);

        try {
            $this->assignments->assign($maintenanceRequest, $technician, $request->user());

            $message = __('Technician :name assigned and appointment booked successfully.', ['name' => $technician->user->name]);
        } catch (TechnicianAssignmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('branch.requests.show', $maintenanceRequest)
            ->with('success', $message);
    }

    /**
     * Unassign the technician, returning the request to approved.
     */
    public function unassign(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($this->branches->ownsRequest($branch, $maintenanceRequest), 404);

        try {
            $this->assignments->unassign($maintenanceRequest, $request->user());
        } catch (TechnicianAssignmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('branch.requests.show', $maintenanceRequest)
            ->with('success', __('Technician unassigned. The request is approved again.'));
    }

    /**
     * Redirect after approval with the standard success flash plus the outcome.
     */
    private function approvedResponse(MaintenanceRequest $maintenanceRequest, string $outcome): RedirectResponse
    {
        return redirect()
            ->route('branch.requests.show', $maintenanceRequest)
            ->with('success', __('Maintenance request approved successfully.'))
            ->with('status', $outcome);
    }
}
