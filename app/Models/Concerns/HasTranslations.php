<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

/**
 * Localized content stored in a dedicated per-model translations table.
 *
 * The using model must define:
 * - translations(): HasMany to its translation model
 * - $translatableFields: list of translatable attribute names
 *
 * Reads fall back to the base column (English) when a translation is missing.
 */
trait HasTranslations
{
    /**
     * Get the translation row for the given locale.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale ??= App::getLocale();

        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Get a translated field with fallback to the base column.
     */
    public function translated(string $field, ?string $locale = null): mixed
    {
        return $this->translate($locale)?->getAttribute($field)
            ?? $this->getAttribute($field);
    }

    /**
     * Get the display name in the current locale (translated or base).
     */
    public function getDisplayNameAttribute(): mixed
    {
        return $this->translated('name');
    }

    /**
     * Persist translations: ['en' => [...], 'ar' => [...]] for translatable fields.
     *
     * Empty rows are removed, so clearing a field reverts to the fallback.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function saveTranslations(array $translations): void
    {
        foreach (['en', 'ar'] as $locale) {
            $attributes = collect($translations[$locale] ?? [])
                ->only($this->translatableFields)
                ->map(fn ($value): ?string => is_string($value) && trim($value) === '' ? null : $value)
                ->all();

            $filled = array_filter($attributes, fn ($value): bool => $value !== null);

            if ($filled === []) {
                $this->translations()->where('locale', $locale)->delete();

                continue;
            }

            $this->translations()->updateOrCreate(['locale' => $locale], $filled);
        }
    }

    /**
     * Scope a query to rows matching the translated (or base) name.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWhereTranslatedName(Builder $query, string $term): Builder
    {
        return $query->where(function ($inner) use ($term): void {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhereHas('translations', fn ($translationQuery): Builder => $translationQuery->where('name', 'like', "%{$term}%"));
        });
    }
}
