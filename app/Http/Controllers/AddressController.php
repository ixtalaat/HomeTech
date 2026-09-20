<?php

namespace App\Http\Controllers;

use App\Exceptions\AddressInUseException;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AddressService $addresses) {}

    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Address::class);

        $addresses = $request->user()->addresses()->latest()->get();

        return view('addresses.index', compact('addresses'));
    }

    /**
     * Show the form for creating a new address.
     */
    public function create(): View
    {
        $this->authorize('create', Address::class);

        return view('addresses.create');
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $this->authorize('create', Address::class);

        $this->addresses->createForUser($request->user(), $request->validated());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Address added successfully.');
    }

    /**
     * Show the form for editing the specified address.
     */
    public function edit(Request $request, Address $address): View
    {
        abort_unless($address->isOwnedBy($request->user()), 404);
        $this->authorize('update', $address);

        return view('addresses.edit', compact('address'));
    }

    /**
     * Update the specified address in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        abort_unless($address->isOwnedBy($request->user()), 404);
        $this->authorize('update', $address);

        $this->addresses->update($address, $request->validated());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->isOwnedBy($request->user()), 404);
        $this->authorize('delete', $address);

        try {
            $this->addresses->delete($address);
        } catch (AddressInUseException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Address deleted successfully.');
    }

    /**
     * Mark the specified address as the default address.
     */
    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->isOwnedBy($request->user()), 404);
        $this->authorize('setDefault', $address);

        $this->addresses->setDefault($address);

        return back()->with('success', 'Default address updated successfully.');
    }
}
