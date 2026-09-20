<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\TechnicianAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTechnicianRequest;
use App\Http\Requests\Admin\ReviewMaintenanceRequestRequest;
use App\Http\Requests\Admin\UpdateRequestAppointmentRequest;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Services\RequestStatusService;
use App\Services\TechnicianAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private RequestStatusService $transitions,
        private TechnicianAssignmentService $assignments
    ) {}

    /**
     * Display a listing of maintenance requests for review.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $query = MaintenanceRequest::with(['user', 'service', 'address'])->latest();

        if ($request->filled('status')) {
            $status = RequestStatus::tryFrom($request->input('status'));

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search): void {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery): Builder => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $requests = $query->paginate(15)->withQueryString();
        $statuses = RequestStatus::cases();

        return view('admin.requests.index', compact('requests', 'statuses'));
    }

    /**
     * Display the specified maintenance request for review.
     */
    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $this->authorize('view', $maintenanceRequest);

        $maintenanceRequest->load(['user', 'service.category', 'address', 'reviewer', 'technician.user', 'statusHistories']);

        $eligibleTechnicians = $this->assignments->eligibleFor($maintenanceRequest);

        return view('admin.requests.show', compact('maintenanceRequest', 'eligibleTechnicians'));
    }

    /**
     * Approve a pending maintenance request.
     */
    public function approve(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $validated = $request->validated();

            if (! empty($validated['preferred_date']) || ! empty($validated['preferred_time'])) {
                $maintenanceRequest->update([
                    'preferred_date' => $validated['preferred_date'] ?? $maintenanceRequest->preferred_date,
                    'preferred_time' => $validated['preferred_time'] ?? $maintenanceRequest->preferred_time,
                ]);
            }

            if (! empty($validated['admin_note'])) {
                $maintenanceRequest->update(['admin_note' => $validated['admin_note']]);
            }

            $this->transitions->transition(
                $maintenanceRequest->refresh(),
                RequestStatus::Approved,
                $request->user(),
                $validated['admin_note'] ?? null
            );

            $maintenanceRequest->update(['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request approved successfully.');
    }

    /**
     * Reject a pending maintenance request.
     */
    public function reject(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $maintenanceRequest->update([
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->transitions->transition(
                $maintenanceRequest->refresh(),
                RequestStatus::Rejected,
                $request->user(),
                $validated['rejection_reason']
            );
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request rejected.');
    }

    /**
     * Request more information from the customer.
     */
    public function requestInfo(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $maintenanceRequest->update([
                'admin_note' => $validated['admin_note'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->transitions->transition(
                $maintenanceRequest->refresh(),
                RequestStatus::InfoRequested,
                $request->user(),
                $validated['admin_note']
            );
        } catch (InvalidStatusTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', 'Information request sent to the customer.');
    }

    /**
     * Change the preferred appointment during review.
     */
    public function updateAppointment(UpdateRequestAppointmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest->update($request->validated());

        return back()->with('success', 'Appointment updated successfully.');
    }

    /**
     * Assign (or reassign) a technician to the request.
     */
    public function assign(AssignTechnicianRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        try {
            $technician = Technician::with('user')->findOrFail($request->validated('technician_id'));

            if ($maintenanceRequest->status === RequestStatus::TechnicianAssigned) {
                $this->assignments->reassign($maintenanceRequest, $technician, $request->user());
                $message = "Request reassigned to '{$technician->user->name}' successfully.";
            } else {
                $this->assignments->assign($maintenanceRequest, $technician, $request->user());
                $message = "Technician '{$technician->user->name}' assigned successfully.";
            }
        } catch (TechnicianAssignmentException $exception) {
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
        abort_unless($request->user()->can('updateAppointment', $maintenanceRequest), 403);

        try {
            $this->assignments->unassign($maintenanceRequest, $request->user());
        } catch (TechnicianAssignmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.requests.show', $maintenanceRequest)
            ->with('success', 'Technician unassigned. The request is approved again.');
    }
}
