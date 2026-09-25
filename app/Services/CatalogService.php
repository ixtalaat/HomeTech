<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogService
{
    /**
     * Paginate services for administration with optional filters.
     *
     * @param  array{search?: ?string, category_id?: ?int, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginateServices(array $filters): LengthAwarePaginator
    {
        $query = Service::with(['category', 'translations', 'category.translations'])->latest();

        if (! empty($filters['category_id'])) {
            $query->where('service_category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Create a service, normalizing the slug and status flag.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createService(array $attributes): Service
    {
        $photo = $this->extractCoverPhoto($attributes);
        $service = Service::create($this->normalizeServiceAttributes($attributes));

        if ($photo !== null) {
            $service->update(['cover_photo' => $photo->store('services', 'public')]);
        }

        $service->saveTranslations($this->arabicAttributes($attributes, ['name', 'description']));

        return $service->refresh();
    }

    /**
     * Update a service, normalizing the slug and status flag.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateService(Service $service, array $attributes): Service
    {
        $photo = $this->extractCoverPhoto($attributes);

        if ($photo !== null) {
            if (is_string($service->cover_photo) && $service->cover_photo !== '') {
                Storage::disk('public')->delete($service->cover_photo);
            }

            $attributes['cover_photo'] = $photo->store('services', 'public');
        } else {
            unset($attributes['cover_photo']);
        }

        $service->update($this->normalizeServiceAttributes($attributes));
        $service->saveTranslations($this->arabicAttributes($attributes, ['name', 'description']));

        return $service->refresh();
    }

    /**
     * Toggle the active status of a service.
     */
    public function toggleServiceStatus(Service $service): Service
    {
        $service->update(['is_active' => ! $service->is_active]);

        return $service->refresh();
    }

    /**
     * Paginate service categories for administration.
     *
     * @return LengthAwarePaginator<int, ServiceCategory>
     */
    public function paginateCategories(): LengthAwarePaginator
    {
        return ServiceCategory::with('translations')->withCount('services')->latest()->paginate(15);
    }

    /**
     * Create a service category, normalizing the slug and status flag.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createCategory(array $attributes): ServiceCategory
    {
        $category = ServiceCategory::create($this->normalizeCategoryAttributes($attributes));
        $category->saveTranslations($this->arabicAttributes($attributes, ['name', 'description']));

        return $category->refresh();
    }

    /**
     * Update a service category, normalizing the slug and status flag.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateCategory(ServiceCategory $category, array $attributes): ServiceCategory
    {
        $category->update($this->normalizeCategoryAttributes($attributes));
        $category->saveTranslations($this->arabicAttributes($attributes, ['name', 'description']));

        return $category->refresh();
    }

    /**
     * Delete a category. Returns false when it still has services.
     */
    public function deleteCategory(ServiceCategory $category): bool
    {
        if ($category->services()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }

    /**
     * Browse the public catalog: active categories with counts plus filtered active services.
     *
     * @return array{categories: Collection<int, ServiceCategory>, services: LengthAwarePaginator<int, Service>, selectedCategory: ?ServiceCategory}
     */
    public function browse(?string $categorySlug, ?string $search): array
    {
        $categories = ServiceCategory::active()
            ->with('translations')
            ->withCount(['services' => fn ($query): Builder => $query->active()])
            ->orderBy('name')
            ->get();

        $selectedCategory = null;
        $servicesQuery = Service::active()
            ->whereHas('category', fn ($query): Builder => $query->active())
            ->with(['category', 'translations', 'category.translations']);

        if (! empty($categorySlug)) {
            $selectedCategory = ServiceCategory::active()->with('translations')->where('slug', $categorySlug)->first();

            if ($selectedCategory !== null) {
                $servicesQuery->where('service_category_id', $selectedCategory->id);
            }
        }

        if (! empty($search)) {
            $this->applySearch($servicesQuery, $search);
        }

        return [
            'categories' => $categories,
            'services' => $servicesQuery->orderBy('name')->paginate(12)->withQueryString(),
            'selectedCategory' => $selectedCategory,
        ];
    }

    /**
     * Constrain services to a keyword across names, descriptions, translations, and categories.
     *
     * Arabic terms also match on a light stem (definite article, plural and
     * feminine suffixes, hamza variants) so inflected forms still find results.
     * Columns are normalized with the same hamza folding on the database side.
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function ($group) use ($search): void {
            foreach ($this->searchTerms($search) as $term) {
                $like = "%{$term}%";
                $group->orWhereRaw($this->normalizedLike('name'), [$like])
                    ->orWhereRaw($this->normalizedLike('description'), [$like])
                    ->orWhereHas('translations', fn ($translationQuery) => $translationQuery
                        ->whereRaw($this->normalizedLike('name'), [$like])
                        ->orWhereRaw($this->normalizedLike('description'), [$like]))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery
                        ->whereRaw($this->normalizedLike('name'), [$like])
                        ->orWhereHas('translations', fn ($categoryTranslationQuery) => $categoryTranslationQuery
                            ->whereRaw($this->normalizedLike('name'), [$like])));
            }
        });
    }

    /**
     * A LIKE expression with Arabic hamza variants folded to a canonical form.
     */
    private function normalizedLike(string $column): string
    {
        $expression = $column;

        foreach (['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ؤ' => 'و', 'ئ' => 'ي'] as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return "{$expression} LIKE ?";
    }

    /**
     * The raw query plus normalized and stemmed variants of each Arabic word.
     *
     * @return list<string>
     */
    private function searchTerms(string $search): array
    {
        $terms = [$search];

        foreach (preg_split('/\s+/u', $search) ?: [] as $word) {
            if (preg_match('/\p{Arabic}/u', $word) !== 1 || mb_strlen($word) < 3) {
                continue;
            }

            $current = $this->normalizeArabic($word);

            if ($current !== $word) {
                $terms[] = $current;
            }

            if (mb_strlen($word) < 4) {
                continue;
            }

            for ($pass = 0; $pass < 3; $pass++) {
                $stripped = $this->stripArabicAffix($current);

                if ($stripped === $current || mb_strlen($stripped) < 3) {
                    break;
                }

                $current = $stripped;
                $terms[] = $current;
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Fold hamza variants and strip diacritics to a canonical search form.
     */
    private function normalizeArabic(string $word): string
    {
        $word = str_replace(['أ', 'إ', 'آ', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'و', 'ي'], $word);

        return (string) preg_replace('/[\x{064B}-\x{0652}]/u', '', $word);
    }

    /**
     * Strip one leading article or trailing suffix from an Arabic word.
     */
    private function stripArabicAffix(string $word): string
    {
        $word = (string) preg_replace('/^(وال|بال|كال|فال|لل|ال)/u', '', $word);

        return (string) preg_replace('/(ات|ون|ين|ان|ها|هم|هن|كم|كن|نا|ة|ه|ك|ي)$/u', '', $word);
    }

    /**
     * Active services in active categories for the XML sitemap.
     *
     * @return Collection<int, Service>
     */
    public function sitemapServices(): Collection
    {
        return Service::active()
            ->whereHas('category', fn ($query): Builder => $query->active())
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'slug', 'updated_at']);
    }

    /**
     * Find an active service in an active category by slug, with related services.
     *
     * @return array{service: Service, relatedServices: Collection<int, Service>}
     */
    public function findServiceForDisplay(string $slug): array
    {
        $service = Service::active()
            ->whereHas('category', fn ($query): Builder => $query->active())
            ->with(['category', 'translations', 'category.translations'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedServices = Service::active()
            ->with('translations')
            ->where('service_category_id', $service->service_category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return compact('service', 'relatedServices');
    }

    /**
     * The most-booked active service per active category for the home page.
     *
     * @return Collection<int, Service>
     */
    public function popularByCategory(): Collection
    {
        $completed = [
            RequestStatus::Completed,
            RequestStatus::Invoiced,
            RequestStatus::Paid,
            RequestStatus::Closed,
        ];

        $popular = collect();

        foreach (ServiceCategory::active()->orderBy('name')->get() as $category) {
            $top = Service::active()
                ->where('service_category_id', $category->id)
                ->with(['category', 'translations', 'category.translations'])
                ->withCount(['maintenanceRequests as completed_jobs_count' => fn ($query): Builder => $query->whereIn('status', $completed)])
                ->orderByDesc('completed_jobs_count')
                ->orderBy('id')
                ->first();

            if ($top !== null) {
                $popular->push($top);
            }
        }

        return $popular;
    }

    /**
     * Active categories with bookable services, for home-page shortcuts.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function spotlightCategories(int $limit = 4): Collection
    {
        return ServiceCategory::active()
            ->whereHas('services', fn ($query) => $query->where('is_active', true))
            ->with('translations')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all categories ordered for form selects.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function orderedCategories(): Collection
    {
        return ServiceCategory::with('translations')->orderBy('name')->get();
    }

    /**
     * Pull an uploaded cover photo out of the attributes, if any.
     */
    private function extractCoverPhoto(array &$attributes): ?UploadedFile
    {
        $photo = $attributes['cover_photo'] ?? null;

        unset($attributes['cover_photo']);

        return $photo instanceof UploadedFile ? $photo : null;
    }

    /**
     * Normalize shared service attributes (slug + status flag).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeServiceAttributes(array $attributes): array
    {
        $attributes['slug'] = ! empty($attributes['slug'])
            ? Str::slug($attributes['slug'])
            : Str::slug($attributes['name']);
        $attributes['is_active'] = $this->parseBoolean($attributes['is_active'] ?? true);

        return $attributes;
    }

    /**
     * Normalize shared category attributes (slug + status flag).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeCategoryAttributes(array $attributes): array
    {
        $attributes['slug'] = ! empty($attributes['slug'])
            ? Str::slug($attributes['slug'])
            : Str::slug($attributes['name']);
        $attributes['is_active'] = $this->parseBoolean($attributes['is_active'] ?? true);

        return $attributes;
    }

    /**
     * Parse a validated boolean-ish value the same way as Request::boolean().
     */
    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Extract Arabic (name_ar/description_ar) inputs into a translations payload.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $fields
     * @return array<string, array<string, mixed>>
     */
    private function arabicAttributes(array $attributes, array $fields): array
    {
        $arabic = [];

        foreach ($fields as $field) {
            if (array_key_exists("{$field}_ar", $attributes)) {
                $arabic[$field] = $attributes["{$field}_ar"];
            }
        }

        return ['ar' => $arabic];
    }
}
