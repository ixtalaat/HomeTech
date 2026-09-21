<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WorkOrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorrectWorkOrderRequest;
use App\Models\AuditLog;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private WorkOrderService $workOrders) {}

    /**
     * Display the specified work order with its audit trail.
     */
    public function show(WorkOrder $workOrder): View
    {
        $this->authorize('view', $workOrder);

        $workOrder->load(['request.service', 'request.address', 'laborItems', 'technician.user']);

        $corrections = AuditLog::where('subject_type', $workOrder->getMorphClass())
            ->where('subject_id', $workOrder->getKey())
            ->with('actor')
            ->latest()
            ->get();

        return view('admin.work-orders.show', compact('workOrder', 'corrections'));
    }

    /**
     * Apply an authorized, logged correction to the work order.
     */
    public function correct(CorrectWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->workOrders->correct(
                $workOrder,
                $request->user(),
                array_filter($validated, fn ($key): bool => in_array($key, ['diagnosis', 'work_notes'], true), ARRAY_FILTER_USE_KEY),
                $validated['reason']
            );
        } catch (WorkOrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Correction applied and logged successfully.'));
    }
}
