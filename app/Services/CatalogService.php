<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
        $query = Service::with('category')->latest();

        if (! empty($filters['category_id'])) {
            $query->where('service_category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
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
        $service = Service::create($this->normalizeServiceAttributes($attributes));
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
        return ServiceCategory::withCount('services')->latest()->paginate(15);
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
            ->withCount(['services' => fn ($query): Builder => $query->active()])
            ->orderBy('name')
            ->get();

        $selectedCategory = null;
        $servicesQuery = Service::active()
            ->whereHas('category', fn ($query): Builder => $query->active())
            ->with('category');

        if (! empty($categorySlug)) {
            $selectedCategory = ServiceCategory::active()->where('slug', $categorySlug)->first();

            if ($selectedCategory !== null) {
                $servicesQuery->where('service_category_id', $selectedCategory->id);
            }
        }

        if (! empty($search)) {
            $servicesQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn ($translationQuery): Builder => $translationQuery->where('name', 'like', "%{$search}%"));
            });
        }

        return [
            'categories' => $categories,
            'services' => $servicesQuery->orderBy('name')->paginate(12)->withQueryString(),
            'selectedCategory' => $selectedCategory,
        ];
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
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedServices = Service::active()
            ->where('service_category_id', $service->service_category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return compact('service', 'relatedServices');
    }

    /**
     * Get all categories ordered for form selects.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function orderedCategories(): Collection
    {
        return ServiceCategory::orderBy('name')->get();
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
