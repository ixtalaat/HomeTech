<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTechnicianRequest;
use App\Http\Requests\Admin\UpdateTechnicianRequest;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of technicians.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Technician::class);

        $query = Technician::with(['user', 'categories'])->withCount('assignedRequests')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $technicians = $query->paginate(15)->withQueryString();

        return view('admin.technicians.index', compact('technicians'));
    }

    /**
     * Show the form for creating a new technician.
     */
    public function create(): View
    {
        $this->authorize('create', Technician::class);

        $categories = ServiceCategory::orderBy('name')->get();

        return view('admin.technicians.create', compact('categories'));
    }

    /**
     * Store a newly created technician (user account + profile) in storage.
     */
    public function store(StoreTechnicianRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $technician = DB::transaction(function () use ($validated): Technician {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'] ?? null,
                'role' => UserRole::Technician,
            ]);

            $technician = $user->technician()->create([
                'phone' => $validated['phone'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'hired_at' => $validated['hired_at'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $technician->categories()->sync($validated['skills'] ?? []);

            return $technician;
        });

        return redirect()
            ->route('admin.technicians.show', $technician)
            ->with('success', 'Technician created successfully.');
    }

    /**
     * Display the specified technician.
     */
    public function show(Technician $technician): View
    {
        $this->authorize('view', $technician);

        $technician->load(['user', 'categories', 'assignedRequests' => fn ($query): HasMany => $query->latest()->limit(10)]);

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

        return view('admin.technicians.edit', compact('technician', 'categories'));
    }

    /**
     * Update the specified technician in storage.
     */
    public function update(UpdateTechnicianRequest $request, Technician $technician): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $technician): void {
            $technician->user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            $technician->update([
                'phone' => $validated['phone'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'hired_at' => $validated['hired_at'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? $technician->is_active),
            ]);

            $technician->categories()->sync($validated['skills'] ?? []);
        });

        return redirect()
            ->route('admin.technicians.show', $technician)
            ->with('success', 'Technician updated successfully.');
    }

    /**
     * Toggle the employment status of the technician.
     */
    public function toggleStatus(Technician $technician): RedirectResponse
    {
        $this->authorize('toggleStatus', $technician);

        $technician->update(['is_active' => ! $technician->is_active]);

        $statusLabel = $technician->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Technician '{$technician->user->name}' was {$statusLabel} successfully.");
    }

    /**
     * Remove the specified technician from storage.
     */
    public function destroy(Technician $technician): RedirectResponse
    {
        $this->authorize('update', $technician);

        if ($technician->assignedRequests()->exists()) {
            return back()->with('error', 'This technician cannot be deleted because they have assigned requests. Deactivate them instead.');
        }

        DB::transaction(function () use ($technician): void {
            $user = $technician->user;
            $technician->delete();

            if ($user !== null) {
                $user->update(['is_active' => false]);
            }
        });

        return redirect()
            ->route('admin.technicians.index')
            ->with('success', 'Technician removed successfully.');
    }
}
