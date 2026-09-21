<?php

namespace App\Http\Controllers\Technician;

use App\Enums\RequestStatus;
use App\Exceptions\AdditionalWorkException;
use App\Exceptions\CompletedWorkOrderException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SchedulingConflictException;
use App\Exceptions\WorkOrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Technician\RecordDiagnosisRequest;
use App\Http\Requests\Technician\RecordNotesRequest;
use App\Http\Requests\Technician\StoreAdditionalWorkRequest;
use App\Http\Requests\Technician\StoreLaborItemRequest;
use App\Http\Requests\Technician\StoreMaterialUsageRequest;
use App\Http\Requests\Technician\UploadWorkPhotosRequest;
use App\Models\AdditionalWork;
use App\Models\InventoryItem;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Services\AdditionalWorkService;
use App\Services\SchedulingService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private WorkOrderService $workOrders,
        private AdditionalWorkService $additionalWork,
        private SchedulingService $scheduling
    ) {}

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
            ->whereIn('status', [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay])
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

        $workOrder->load(['request.service', 'request.address', 'request.appointment', 'laborItems', 'materialUsages.item', 'additionalWorkItems.requester', 'technician.user']);

        $stockedItems = InventoryItem::inStock()->orderBy('name')->get();

        return view('technician.jobs.show', compact('workOrder', 'stockedItems'));
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
     * Mark the technician as on the way to the job.
     */
    public function onWay(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless(
            $maintenanceRequest->technician !== null
                && $maintenanceRequest->technician->user_id === $request->user()->id,
            404
        );

        try {
            $this->scheduling->markOnWay($maintenanceRequest, $request->user());
        } catch (SchedulingConflictException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Customer notified that you are on the way.');
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
     * Record material usage, decrementing stock.
     */
    public function recordMaterial(StoreMaterialUsageRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $item = InventoryItem::findOrFail($validated['inventory_item_id']);

            $this->workOrders->recordMaterialUsage($workOrder, $request->user(), $item, (int) $validated['quantity']);
        } catch (CompletedWorkOrderException|InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Material usage recorded and stock updated.');
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
     * Request additional work approval from the customer.
     */
    public function requestAdditional(StoreAdditionalWorkRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->additionalWork->request(
                $workOrder,
                $request->user(),
                $validated['description'],
                (float) $validated['cost']
            );
        } catch (AdditionalWorkException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Additional work sent to the customer for approval.');
    }

    /**
     * Mark approved additional work as performed.
     */
    public function completeAdditional(Request $request, AdditionalWork $additionalWork): RedirectResponse
    {
        $workOrder = $additionalWork->workOrder;

        abort_unless($this->ownsWorkOrder($request, $workOrder), 404);
        $this->authorize('update', $workOrder);

        try {
            $this->additionalWork->markCompleted($additionalWork, $request->user());
        } catch (AdditionalWorkException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Additional work marked as performed.');
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
