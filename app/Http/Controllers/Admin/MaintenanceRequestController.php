<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Exceptions\BillingException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\SchedulingConflictException;
use App\Exceptions\TechnicianAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTechnicianRequest;
use App\Http\Requests\Admin\BookAppointmentRequest;
use App\Http\Requests\Admin\CancelRequestRequest;
use App\Http\Requests\Admin\RescheduleAppointmentRequest;
use App\Http\Requests\Admin\ReviewMaintenanceRequestRequest;
use App\Http\Requests\Admin\UpdateRequestAppointmentRequest;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Notifications\RescheduleNeeded;
use App\Services\CancellationService;
use App\Services\MaintenanceRequestService;
use App\Services\RequestReviewService;
use App\Services\SchedulingService;
use App\Services\TechnicianAssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MaintenanceRequestService $requests,
        private RequestReviewService $reviews,
        private TechnicianAssignmentService $assignments,
        private SchedulingService $scheduling,
        private CancellationService $cancellations
    ) {}

    /**
     * Display a listing of maintenance requests for review.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $requests = $this->requests->paginateForAdmin($request->only(['search', 'status']));
        $statuses = RequestStatus::cases();

        return view('admin.requests.index', compact('requests', 'statuses'));
    }

    /**
     * Display the specified maintenance request for review.
     */
    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $this->authorize('view', $maintenanceRequest);

        $maintenanceRequest->load(['user', 'service.category', 'address', 'reviewer', 'technician.user', 'appointment', 'workOrder', 'invoice', 'cancellation', 'review.user', 'statusHistories']);

        $eligibleTechnicians = $this->assignments->eligibleWithSlotStatus($maintenanceRequest);

        return view('admin.requests.show', compact('maintenanceRequest', 'eligibleTechnicians'));
    }

    /**
     * Approve a pending maintenance request, then auto-assign a technician.
     *
     * Auto-assignment is best effort: when nobody qualifies, the request
     * stays approved and unassigned with the reason flashed for staff.
     */
    public function approve(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $this->reviews->approve($maintenanceRequest, $request->user(), $request->validated());
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        try {
            $result = $this->assignments->autoAssign($maintenanceRequest->refresh(), $request->user());
        } catch (TechnicianAssignmentException $exception) {
            return redirect()
                ->route('admin.requests.show', $maintenanceRequest)
                ->with('success', __('Maintenance request approved successfully.'))
                ->with('status', $exception->getMessage());
        }

        if ($result['technician'] === null) {
            if (in_array($result['code'], ['no_match', 'past_slot'], true)) {
                $maintenanceRequest->user->notify(new RescheduleNeeded($maintenanceRequest, (string) $result['reason']));
            }

            return redirect()
                ->route('admin.requests.show', $maintenanceRequest)
                ->with('success', __('Maintenance request approved successfully.'))
                ->with('status', __('Automatic assignment skipped: :reason', ['reason' => $result['reason']]));
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Maintenance request approved successfully.'))
            ->with('status', __('Automatically assigned to :name.', ['name' => $result['technician']->user->name]));
    }

    /**
     * Reject a pending maintenance request.
     */
    public function reject(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $this->reviews->reject(
                $maintenanceRequest,
                $request->user(),
                $request->validated('rejection_reason')
            );
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Maintenance request rejected.'));
    }

    /**
     * Request more information from the customer.
     */
    public function requestInfo(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $this->reviews->requestInfo(
                $maintenanceRequest,
                $request->user(),
                $request->validated('admin_note')
            );
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Information request sent to the customer.'));
    }

    /**
     * Change the preferred appointment during review.
     */
    public function updateAppointment(UpdateRequestAppointmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->reviews->updateAppointment($maintenanceRequest, $request->validated());

        return back()->with('success', __('Appointment updated successfully.'));
    }

    /**
     * Assign (or reassign) a technician to the request.
     */
    public function assign(AssignTechnicianRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $technician = Technician::with('user')->findOrFail($request->validated('technician_id'));

            if ($maintenanceRequest->status === RequestStatus::Scheduled) {
                $this->assignments->reassign($maintenanceRequest, $technician, $request->user());
                $message = __('Request reassigned to :name successfully.', ['name' => $technician->user->name]);
            } else {
                $this->assignments->assign($maintenanceRequest, $technician, $request->user());
                $message = __('Technician :name assigned and appointment booked successfully.', ['name' => $technician->user->name]);
            }
        } catch (TechnicianAssignmentException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (SchedulingConflictException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', $message);
    }

    /**
     * Unassign the technician, returning the request to approved.
     */
    public function unassign(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless($request->user()->can('manageAppointment', $maintenanceRequest), 403);

        try {
            $this->assignments->unassign($maintenanceRequest, $request->user());
        } catch (TechnicianAssignmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Technician unassigned. The request is approved again.'));
    }

    /**
     * Cancel the maintenance request per the cancellation policy.
     */
    public function cancelRequest(CancelRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $this->cancellations->cancel(
                $maintenanceRequest,
                $request->user(),
                $request->validated('reason')
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Request cancelled per the cancellation policy.'));
    }

    /**
     * Book an appointment for an assigned request awaiting a slot.
     */
    public function bookAppointment(BookAppointmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->scheduling->book(
                $maintenanceRequest,
                $maintenanceRequest->technician,
                $validated['date'],
                $validated['start_time'],
                $validated['end_time'],
                $request->user()
            );
        } catch (SchedulingConflictException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Appointment booked successfully.'));
    }

    /**
     * Reschedule the appointment, re-running the conflict check.
     */
    public function rescheduleAppointment(RescheduleAppointmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->scheduling->reschedule(
                $maintenanceRequest->appointment,
                $validated['date'],
                $validated['start_time'],
                $validated['end_time'],
                $request->user()
            );
        } catch (SchedulingConflictException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Appointment rescheduled successfully.'));
    }

    /**
     * Cancel the appointment, returning the request for rebooking.
     */
    public function cancelAppointment(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless($request->user()->can('manageAppointment', $maintenanceRequest), 403);
        abort_unless(in_array($maintenanceRequest->status, [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true) && $maintenanceRequest->appointment !== null, 404);

        $this->scheduling->cancel($maintenanceRequest->appointment, $request->user());

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', __('Appointment cancelled. The request is awaiting a new slot.'));
    }
}
