<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Exceptions\BillingException;
use App\Http\Requests\CancelMaintenanceRequestRequest;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Services\CancellationService;
use App\Services\MaintenanceRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MaintenanceRequestService $requests,
        private CancellationService $cancellations
    ) {}

    /**
     * Display a listing of the user's maintenance requests.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $requests = $this->requests->paginateForUser($request->user(), $request->input('status'));
        $statuses = RequestStatus::cases();

        return view('requests.index', compact('requests', 'statuses'));
    }

    /**
     * Show the form for creating a new maintenance request.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', MaintenanceRequest::class);

        ['services' => $services, 'addresses' => $addresses] = $this->requests->createFormData($request->user());
        $selectedService = $request->integer('service_id') ?: null;

        return view('requests.create', compact('services', 'addresses', 'selectedService'));
    }

    /**
     * Store a newly created maintenance request in storage.
     */
    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', MaintenanceRequest::class);

        $maintenanceRequest = $this->requests->create(
            $request->user(),
            $request->validated(),
            $request->file('photos', [])
        );

        return redirect()
            ->route('requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request submitted successfully. It is now pending review.');
    }

    /**
     * Display the specified maintenance request with its status history.
     */
    public function show(Request $request, MaintenanceRequest $maintenanceRequest): View
    {
        abort_unless($maintenanceRequest->isOwnedBy($request->user()), 404);
        $this->authorize('view', $maintenanceRequest);

        $maintenanceRequest->load(['service.category', 'address', 'appointment', 'workOrder.additionalWorkItems', 'invoice', 'cancellation', 'review', 'statusHistories']);

        return view('requests.show', compact('maintenanceRequest'));
    }

    /**
     * Cancel the maintenance request per the cancellation policy.
     */
    public function cancel(CancelMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless($maintenanceRequest->isOwnedBy($request->user()), 404);

        try {
            $cancellation = $this->cancellations->cancel(
                $maintenanceRequest,
                $request->user(),
                $request->validated('reason')
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $cancellation->fee > 0
            ? "Request cancelled with a fee of {$cancellation->fee} EGP per the cancellation policy."
            : 'Request cancelled without a fee.';

        return redirect()
            ->route('requests.show', $maintenanceRequest)
            ->with('success', $message);
    }
}
