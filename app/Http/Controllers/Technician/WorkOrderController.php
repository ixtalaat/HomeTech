<?php

namespace App\Http\Controllers\Technician;

use App\Enums\RequestStatus;
use App\Exceptions\CompletedWorkOrderException;
use App\Exceptions\WorkOrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Technician\RecordDiagnosisRequest;
use App\Http\Requests\Technician\RecordNotesRequest;
use App\Http\Requests\Technician\StoreLaborItemRequest;
use App\Http\Requests\Technician\UploadWorkPhotosRequest;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private WorkOrderService $workOrders) {}

    /**
     * Display the technician's jobs: scheduled requests awaiting start plus work orders.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkOrder::class);

        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        $awaitingStart = MaintenanceRequest::with(['service', 'address', 'appointment'])
            ->where('technician_id', $technician->id)
            ->where('status', RequestStatus::Scheduled)
            ->whereDoesntHave('workOrder')
            ->latest()
            ->get();

        $workOrders = WorkOrder::forTechnician($technician->id)
            ->with(['request.service', 'request.address'])
            ->latest()
            ->paginate(15);

        return view('technician.jobs.index', compact('awaitingStart', 'workOrders'));
    }

    /**
     * Display the specified work order.
     */
    public function show(Request $request, WorkOrder $workOrder): View
    {
        abort_unless($this->ownsWorkOrder($request, $workOrder), 404);
        $this->authorize('view', $workOrder);

        $workOrder->load(['request.service', 'request.address', 'request.appointment', 'laborItems', 'technician.user']);

        return view('technician.jobs.show', compact('workOrder'));
    }

    /**
     * Start the visit for an assigned scheduled request.
     */
    public function start(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless(
            $maintenanceRequest->technician !== null
                && $maintenanceRequest->technician->user_id === $request->user()->id,
            404
        );

        try {
            $workOrder = $this->workOrders->startVisit($maintenanceRequest, $request->user());
        } catch (WorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('technician.jobs.show', $workOrder)
            ->with('success', 'Visit started. The job is now in progress.');
    }

    /**
     * Record the diagnosis.
     */
    public function recordDiagnosis(RecordDiagnosisRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $this->workOrders->recordDiagnosis($workOrder, $request->user(), $request->validated('diagnosis'));
        } catch (CompletedWorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Diagnosis recorded successfully.');
    }

    /**
     * Record work notes.
     */
    public function recordNotes(RecordNotesRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $this->workOrders->recordNotes($workOrder, $request->user(), $request->validated('work_notes'));
        } catch (CompletedWorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Work notes recorded successfully.');
    }

    /**
     * Add a labor item.
     */
    public function addLabor(StoreLaborItemRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->workOrders->addLaborItem($workOrder, $request->user(), $validated['description'], (float) $validated['cost']);
        } catch (CompletedWorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Labor item added successfully.');
    }

    /**
     * Upload before/after photos.
     */
    public function uploadPhotos(UploadWorkPhotosRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->workOrders->uploadPhotos($workOrder, $request->user(), $validated['slot'], $request->file('photos', []));
        } catch (CompletedWorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Photos uploaded successfully.');
    }

    /**
     * Complete the work order.
     */
    public function complete(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        abort_unless($this->ownsWorkOrder($request, $workOrder), 404);
        $this->authorize('update', $workOrder);

        try {
            $this->workOrders->complete($workOrder, $request->user());
        } catch (WorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Job completed successfully. An invoice can now be generated.');
    }

    /**
     * Determine whether the work order belongs to the authenticated technician.
     */
    private function ownsWorkOrder(Request $request, WorkOrder $workOrder): bool
    {
        return $workOrder->technician !== null
            && $workOrder->technician->user_id === $request->user()->id;
    }
}
