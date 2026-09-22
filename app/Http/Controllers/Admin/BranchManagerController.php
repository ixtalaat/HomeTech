<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exceptions\BranchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateManagerRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchManagerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private BranchService $branches) {}

    /**
     * Display all branch-manager accounts with their branches.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $managers = $this->branches->paginateManagers();

        return view('admin.managers.index', compact('managers'));
    }

    /**
     * Toggle the employment status of a branch manager.
     */
    public function toggleStatus(User $manager): RedirectResponse
    {
        $this->authorize('toggleStatus', $manager);
        abort_unless($manager->role === UserRole::Manager, 404);

        $manager = $this->branches->toggleManagerStatus($manager);
        $statusLabel = $manager->is_active ? 'activated' : 'deactivated';

        return back()->with('success', __('Manager :name was :status successfully.', ['name' => $manager->name, 'status' => $statusLabel]));
    }

    /**
     * Remove the manager from their branch, keeping the account.
     */
    public function unassign(User $manager): RedirectResponse
    {
        $this->authorize('update', $manager);
        abort_unless($manager->role === UserRole::Manager, 404);

        $branch = $manager->managedBranch;

        if ($branch !== null) {
            $this->branches->assignManager($branch, ['manager_user_id' => null]);
        }

        return back()->with('success', __('Manager unassigned successfully.'));
    }

    /**
     * Show the form for editing a branch manager.
     */
    public function edit(User $manager): View
    {
        $this->authorize('update', $manager);
        abort_unless($manager->role === UserRole::Manager, 404);

        $branches = Branch::where('is_active', true)->orderByDesc('priority')->orderBy('name')->get();

        return view('admin.managers.edit', compact('manager', 'branches'));
    }

    /**
     * Update a branch manager in storage.
     */
    public function update(UpdateManagerRequest $request, User $manager): RedirectResponse
    {
        try {
            $this->branches->updateManager($manager, $request->validated());
        } catch (BranchException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.managers.index')
            ->with('success', __('Manager updated successfully.'));
    }
}
