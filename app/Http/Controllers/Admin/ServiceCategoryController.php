<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    /**
     * Display a listing of service categories.
     */
    public function index(): View
    {
        $categories = $this->catalog->paginateCategories();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new service category.
     */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created service category in storage.
     */
    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $this->catalog->createCategory($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Service category created successfully.'));
    }

    /**
     * Show the form for editing the specified service category.
     */
    public function edit(ServiceCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified service category in storage.
     */
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $this->catalog->updateCategory($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Service category updated successfully.'));
    }

    /**
     * Remove the specified service category from storage.
     */
    public function destroy(ServiceCategory $category): RedirectResponse
    {
        if (! $this->catalog->deleteCategory($category)) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', __('Cannot delete a category with existing services. Please reassign or delete its services first.'));
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Service category deleted successfully.'));
    }
}
