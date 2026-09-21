<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasTranslations;

    /**
     * Translatable attributes stored in the translations table.
     *
     * @var list<string>
     */
    protected array $translatableFields = ['name', 'description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'estimated_duration_minutes',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (self $service): void {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * Scope a query to only include active services.
     *
     * @param  Builder<Service>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter services by category ID.
     *
     * @param  Builder<Service>  $query
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('service_category_id', $categoryId);
    }

    /**
     * Get the category that owns the service.
     *
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * Get the translations for the service.
     *
     * @return HasMany<ServiceTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }
}
