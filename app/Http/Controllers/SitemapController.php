<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    /**
     * Render the XML sitemap for public pages and bookable services.
     */
    public function __invoke(): Response
    {
        $urls = Cache::remember('sitemap-urls', 60 * 60, fn (): array => [
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('services.index'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ...$this->catalog->sitemapServices()->map(fn ($service): array => [
                'loc' => route('services.show', $service->slug),
                'lastmod' => $service->updated_at->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ])->all(),
            ['loc' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('contact.create'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ]);

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'text/xml');
    }
}
