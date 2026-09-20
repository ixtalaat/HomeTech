<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceBrowseController extends Controller
{
    /**
     * Display a listing of available active services for public/customers.
     */
    public function index(Request $request): View
    {
        $categories = ServiceCategory::query()
            ->active()
            ->withCount(['services' => function ($query): void {
                $query->active();
            }])
            ->orderBy('name')
            ->get();

        $selectedCategorySlug = $request->query('category');
        $selectedCategory = null;

        $servicesQuery = Service::query()
            ->active()
            ->whereHas('category', function ($query): void {
                $query->active();
            })
            ->with('category');

        if (! empty($selectedCategorySlug)) {
            $selectedCategory = ServiceCategory::query()
                ->active()
                ->where('slug', $selectedCategorySlug)
                ->first();

            if ($selectedCategory) {
                $servicesQuery->where('service_category_id', $selectedCategory->id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $servicesQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $servicesQuery->orderBy('name')->paginate(12)->withQueryString();

        return view('services.index', compact('categories', 'services', 'selectedCategory'));
    }

    /**
     * Display the specified active service details.
     */
    public function show(string $slug): View
    {
        $service = Service::query()
            ->active()
            ->whereHas('category', function ($query): void {
                $query->active();
            })
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedServices = Service::query()
            ->active()
            ->where('service_category_id', $service->service_category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'relatedServices'));
    }
}
