<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private CustomerService $customers) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $customers = $this->customers->paginate($request->only(['search', 'status']));

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Display the specified customer.
     */
    public function show(User $customer): View
    {
        $this->authorize('view', $customer);
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->load(['addresses' => fn ($query): HasMany => $query->latest()]);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(User $customer): View
    {
        $this->authorize('update', $customer);
        abort_unless($customer->role === UserRole::Customer, 404);

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $this->customers->update($customer, $request->validated());

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Toggle the active status of the customer.
     */
    public function toggleStatus(User $customer): RedirectResponse
    {
        $this->authorize('toggleStatus', $customer);
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer = $this->customers->toggleStatus($customer);
        $statusLabel = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer '{$customer->name}' was {$statusLabel} successfully.");
    }
}
