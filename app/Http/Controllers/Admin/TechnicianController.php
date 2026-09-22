<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTechnicianRequest;
use App\Http\Requests\Admin\UpdateTechnicianRequest;
use App\Models\Branch;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Services\TechnicianService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TechnicianService $technicians) {}

    /**
     * Display a listing of technicians.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Technician::class);

        $technicians = $this->technicians->paginate($request->only(['search', 'status']));

        return view('admin.technicians.index', compact('technicians'));
    }

    /**
     * Show the form for creating a new technician.
     */
    public function create(): View
    {
        $this->authorize('create', Technician::class);

        $categories = ServiceCategory::orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderByDesc('priority')->orderBy('name')->get();

        return view('admin.technicians.create', compact('categories', 'branches'));
    }

    /**
     * Store a newly created technician (user account + profile) in storage.
     */
    public function store(StoreTechnicianRequest $request): RedirectResponse
    {
        $technician = $this->technicians->create($request->validated());

        return redirect()
            ->route('admin.technicians.show', $technician)
            ->with('success', __('Technician created successfully.'));
    }

    /**
     * Display the specified technician.
     */
    public function show(Technician $technician): View
    {
        $this->authorize('view', $technician);

        $technician->load(['user', 'categories', 'branch', 'schedules', 'assignedRequests' => fn ($query): HasMany => $query->latest()->limit(10)]);

        return view('admin.technicians.show', compact('technician'));
    }

    /**
     * Show the form for editing the specified technician.
     */
    public function edit(Technician $technician): View
    {
        $this->authorize('update', $technician);

        $technician->load(['user', 'categories']);
        $categories = ServiceCategory::orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderByDesc('priority')->orderBy('name')->get();

        return view('admin.technicians.edit', compact('technician', 'categories', 'branches'));
    }

    /**
     * Update the specified technician in storage.
     */
    public function update(UpdateTechnicianRequest $request, Technician $technician): RedirectResponse
    {
        $this->technicians->update($technician, $request->validated());

        return redirect()
            ->route('admin.technicians.show', $technician)
            ->with('success', __('Technician updated successfully.'));
    }

    /**
     * Toggle the employment status of the technician.
     */
    public function toggleStatus(Technician $technician): RedirectResponse
    {
        $this->authorize('toggleStatus', $technician);

        $technician = $this->technicians->toggleStatus($technician);
        $statusLabel = $technician->is_active ? 'activated' : 'deactivated';

        return back()->with('success', __('Technician :name was :status successfully.', ['name' => $technician->user->name, 'status' => $statusLabel]));
    }

    /**
     * Remove the specified technician from storage.
     */
    public function destroy(Technician $technician): RedirectResponse
    {
        $this->authorize('update', $technician);

        if (! $this->technicians->delete($technician)) {
            return back()->with('error', __('This technician cannot be deleted because they have assigned requests. Deactivate them instead.'));
        }

        return redirect()
            ->route('admin.technicians.index')
            ->with('success', __('Technician removed successfully.'));
    }
}
