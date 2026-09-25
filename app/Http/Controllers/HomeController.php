<?php

namespace App\Http\Controllers;

use App\Services\BranchService;
use App\Services\CatalogService;
use App\Services\ReportingService;
use App\Services\ReviewService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private ReportingService $reports,
        private ReviewService $reviews,
        private BranchService $branches
    ) {}

    /**
     * Display the home page with popular services and live highlights.
     */
    public function index(): View
    {
        return view('home', [
            'popularServices' => $this->catalog->popularByCategory(),
            'quickCategories' => $this->catalog->spotlightCategories(),
            'testimonials' => $this->reviews->spotlight(),
            'stats' => $this->reports->homeStats(),
            'servedCities' => $this->branches->servedCities(),
        ]);
    }
}
