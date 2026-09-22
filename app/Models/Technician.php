<?php

namespace App\Models;

use Database\Factories\TechnicianFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technician extends Model
{
    /** @use HasFactory<TechnicianFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'phone',
        'emergency_contact',
        'is_active',
        'notes',
        'hired_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hired_at' => 'date',
        ];
    }

    /**
     * Get the user account for the technician.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service categories the technician supports.
     *
     * @return BelongsToMany<ServiceCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'category_technician')->withTimestamps();
    }

    /**
     * Get the branch the technician belongs to.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the weekly work schedule rows for the technician.
     *
     * @return HasMany<TechnicianSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(TechnicianSchedule::class);
    }

    /**
     * Get the maintenance requests currently assigned to the technician.
     *
     * @return HasMany<MaintenanceRequest, $this>
     */
    public function assignedRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * Scope a query to only include active technicians.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine whether the technician supports the given category.
     */
    public function supportsCategory(int $categoryId): bool
    {
        return $this->categories()->where('service_categories.id', $categoryId)->exists();
    }

    /**
     * Determine whether the technician is eligible for the given category (BR-001 predicate).
     *
     * The technician must be active, linked to an active user account,
     * and support the service category.
     */
    public function isAvailableFor(ServiceCategory $category): bool
    {
        return $this->is_active
            && $this->user !== null
            && $this->user->is_active
            && $this->supportsCategory($category->id);
    }
}
