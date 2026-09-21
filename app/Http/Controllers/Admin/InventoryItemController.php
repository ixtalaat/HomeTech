<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Http\Requests\Admin\StoreInventoryItemRequest;
use App\Http\Requests\Admin\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private InventoryService $inventory) {}

    /**
     * Display a listing of inventory items with the low-stock report.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        $query = InventoryItem::latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->input('filter') === 'low-stock') {
            $query->lowStock();
        }

        $items = $query->paginate(15)->withQueryString();
        $lowStockItems = InventoryItem::lowStock()->orderBy('current_stock')->limit(10)->get();

        return view('admin.inventory.index', compact('items', 'lowStockItems'));
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create(): View
    {
        $this->authorize('create', InventoryItem::class);

        return view('admin.inventory.create');
    }

    /**
     * Store a newly created inventory item in storage.
     */
    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $item = $this->inventory->createItem($request->validated(), $request->user());

        return redirect()
            ->route('admin.inventory.show', $item)
            ->with('success', __('Inventory item created successfully.'));
    }

    /**
     * Display the specified inventory item with its movement ledger.
     */
    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);

        $inventoryItem->load(['movements' => fn ($query): HasMany => $query->latest()->limit(50)]);

        return view('admin.inventory.show', compact('inventoryItem'));
    }

    /**
     * Show the form for editing the specified inventory item.
     */
    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);

        return view('admin.inventory.edit', compact('inventoryItem'));
    }

    /**
     * Update the specified inventory item in storage.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->inventory->updateItem($inventoryItem, $request->validated());

        return redirect()
            ->route('admin.inventory.show', $inventoryItem)
            ->with('success', __('Inventory item updated successfully.'));
    }

    /**
     * Apply a manual stock adjustment.
     */
    public function adjust(AdjustInventoryRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->inventory->adjust($inventoryItem, (int) $validated['delta'], $request->user(), $validated['reason']);
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Stock adjusted successfully.'));
    }

    /**
     * Remove the specified inventory item from storage.
     */
    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);

        if (! $this->inventory->deleteItem($inventoryItem)) {
            return back()->with('error', __('This item cannot be deleted because it has movement or usage history.'));
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', __('Inventory item deleted successfully.'));
    }
}
