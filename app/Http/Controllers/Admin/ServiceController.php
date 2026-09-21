<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    /**
     * Display a listing of services for administration.
     */
    public function index(Request $request): View
    {
        $services = $this->catalog->paginateServices($request->only(['search', 'category_id', 'status']));
        $categories = $this->catalog->orderedCategories();

        return view('admin.services.index', compact('services', 'categories'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): View
    {
        $categories = $this->catalog->orderedCategories();

        return view('admin.services.create', compact('categories'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->catalog->createService($request->validated());

        return redirect()
            ->route('admin.services.index')
            ->with('success', __('Service created successfully.'));
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service): View
    {
        $categories = $this->catalog->orderedCategories();

        return view('admin.services.edit', compact('service', 'categories'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->catalog->updateService($service, $request->validated());

        return redirect()
            ->route('admin.services.index')
            ->with('success', __('Service updated successfully.'));
    }

    /**
     * Toggle the active status of the service.
     */
    public function toggleStatus(Service $service): RedirectResponse
    {
        $service = $this->catalog->toggleServiceStatus($service);
        $statusLabel = $service->is_active ? 'activated' : 'deactivated';

        return back()->with('success', __('Service :name was :status successfully.', ['name' => $service->name, 'status' => $statusLabel]));
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with('success', __('Service deleted successfully.'));
    }
}
