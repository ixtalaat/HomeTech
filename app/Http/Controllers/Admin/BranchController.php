<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exceptions\BranchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private BranchService $branches) {}

    /**
     * Display a listing of branches.
     */
    public function index(): View
    {
        $branches = $this->branches->paginate();

        return view('admin.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(): View
    {
        $managers = $this->eligibleManagers();

        return view('admin.branches.create', compact('managers'));
    }

    /**
     * Store a newly created branch with its cities in storage.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        try {
            $this->branches->createBranch($request->validated());
        } catch (BranchException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('Branch created successfully.'));
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): View
    {
        $branch->load(['cities', 'manager']);
        $managers = $this->eligibleManagers($branch);

        return view('admin.branches.edit', compact('branch', 'managers'));
    }

    /**
     * Update the specified branch with its cities in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $this->branches->updateBranch($branch, $request->validated());
        } catch (BranchException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('Branch updated successfully.'));
    }

    /**
     * Remove the specified branch when nothing uses it.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        if (! $this->branches->deleteBranch($branch)) {
            return back()->with('error', __('This branch cannot be deleted because technicians or cities still use it.'));
        }

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('Branch deleted successfully.'));
    }

    /**
     * Manager accounts eligible for assignment: active branch managers who
     * do not manage another branch, plus this branch's current manager.
     *
     * @return Collection<int, User>
     */
    private function eligibleManagers(?Branch $branch = null): Collection
    {
        $taken = Branch::whereNotNull('manager_user_id')
            ->when($branch !== null, fn ($query) => $query->whereKeyNot($branch->id))
            ->pluck('manager_user_id');

        return User::where('role', UserRole::Manager)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNotIn('id', $taken)->orWhere('id', $branch?->manager_user_id ?? 0))
            ->orderBy('name')
            ->get();
    }
}
