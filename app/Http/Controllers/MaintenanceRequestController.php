<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the user's maintenance requests.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $query = $request->user()->maintenanceRequests()->with(['service', 'address'])->latest();

        if ($request->filled('status')) {
            $status = RequestStatus::tryFrom($request->input('status'));

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $requests = $query->paginate(15)->withQueryString();
        $statuses = RequestStatus::cases();

        return view('requests.index', compact('requests', 'statuses'));
    }

    /**
     * Show the form for creating a new maintenance request.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', MaintenanceRequest::class);

        $services = Service::active()->whereHas('category', fn ($query): Builder => $query->where('is_active', true))->orderBy('name')->get();
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->latest()->get();
        $selectedService = $request->integer('service_id') ?: null;

        return view('requests.create', compact('services', 'addresses', 'selectedService'));
    }

    /**
     * Store a newly created maintenance request in storage.
     */
    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', MaintenanceRequest::class);

        $validated = $request->validated();

        $maintenanceRequest = DB::transaction(function () use ($request, $validated): MaintenanceRequest {
            $maintenanceRequest = $request->user()->maintenanceRequests()->create([
                ...$validated,
                'status' => RequestStatus::PendingReview,
                'photos' => null,
            ]);

            $photoPaths = [];
            foreach ($request->file('photos', []) as $photo) {
                $photoPaths[] = $photo->store("request-photos/{$maintenanceRequest->id}", 'public');
            }

            if ($photoPaths !== []) {
                $maintenanceRequest->update(['photos' => $photoPaths]);
            }

            $maintenanceRequest->statusHistories()->create([
                'from_status' => null,
                'status' => RequestStatus::PendingReview->value,
                'changed_by' => $request->user()->id,
                'reason' => null,
            ]);

            return $maintenanceRequest;
        });

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

        $maintenanceRequest->load(['service.category', 'address', 'statusHistories']);

        return view('requests.show', compact('maintenanceRequest'));
    }
}
