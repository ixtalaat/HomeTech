<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'maintenance_request_id',
        'appointment_id',
        'technician_id',
        'status',
        'diagnosis',
        'work_notes',
        'before_photos',
        'after_photos',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'before_photos' => 'array',
            'after_photos' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the maintenance request for the work order.
     *
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    /**
     * Get the appointment for the work order.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the technician performing the work order.
     *
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Get the labor items recorded for the work order.
     *
     * @return HasMany<LaborItem, $this>
     */
    public function laborItems(): HasMany
    {
        return $this->hasMany(LaborItem::class);
    }

    /**
     * Scope a query to work orders of the given technician.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Get the total labor cost.
     */
    public function laborTotal(): float
    {
        return (float) $this->laborItems()->sum('cost');
    }

    /**
     * Determine whether the work order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === WorkOrderStatus::Completed;
    }

    /**
     * Determine whether the completion requirements are met.
     *
     * Labor/materials hooks: Epic 8 extends material checks; additional-work
     * resolution is wired in Epic 9. Hard blockers now: diagnosis + notes.
     *
     * @return array<int, string> List of unmet requirement messages.
     */
    public function unmetCompletionRequirements(): array
    {
        $missing = [];

        if (empty(trim((string) $this->diagnosis))) {
            $missing[] = 'A diagnosis must be recorded before completion.';
        }

        if (empty(trim((string) $this->work_notes))) {
            $missing[] = 'Work notes must be recorded before completion.';
        }

        return $missing;
    }
}
