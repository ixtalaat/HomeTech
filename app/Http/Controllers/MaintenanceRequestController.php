<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private MaintenanceRequestService $requests) {}

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

        $maintenanceRequest->load(['service.category', 'address', 'appointment', 'workOrder.additionalWorkItems', 'statusHistories']);

        return view('requests.show', compact('maintenanceRequest'));
    }
}
