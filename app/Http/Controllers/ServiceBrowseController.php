<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceBrowseController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    /**
     * Display a listing of available active services for public/customers.
     */
    public function index(Request $request): View
    {
        ['categories' => $categories, 'services' => $services, 'selectedCategory' => $selectedCategory] = $this->catalog->browse(
            $request->query('category'),
            $request->query('search')
        );

        return view('services.index', compact('categories', 'services', 'selectedCategory'));
    }

    /**
     * Display the specified active service details.
     */
    public function show(string $slug): View
    {
        ['service' => $service, 'relatedServices' => $relatedServices] = $this->catalog->findServiceForDisplay($slug);

        return view('services.show', compact('service', 'relatedServices'));
    }
}
