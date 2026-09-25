<?php

namespace App\Http\Controllers;

use App\Services\BranchService;
use App\Services\CatalogService;
use App\Services\ReportingService;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Cache window for home page aggregates, in seconds.
     */
    private const CACHE_TTL = 10 * 60;

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
            'popularServices' => Cache::remember('home:popular-services', self::CACHE_TTL, fn () => $this->catalog->popularByCategory()),
            'quickCategories' => Cache::remember('home:quick-categories', self::CACHE_TTL, fn () => $this->catalog->spotlightCategories()),
            'testimonials' => Cache::remember('home:testimonials', self::CACHE_TTL, fn () => $this->reviews->spotlight()),
            'stats' => Cache::remember('home:stats', self::CACHE_TTL, fn () => $this->reports->homeStats()),
            'servedCities' => Cache::remember('home:served-cities', self::CACHE_TTL, fn () => $this->branches->servedCities()),
        ]);
    }
}
