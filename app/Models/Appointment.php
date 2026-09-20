<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'maintenance_request_id',
        'technician_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'date' => 'date',
        ];
    }

    /**
     * Get the maintenance request for the appointment.
     *
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    /**
     * Get the technician for the appointment.
     *
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Scope a query to appointments of a technician on a given date.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForTechnicianOn(Builder $query, int $technicianId, string $date): Builder
    {
        return $query->where('technician_id', $technicianId)->where('date', $date);
    }

    /**
     * Scope a query to appointments occupying calendar space (not cancelled).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled);
    }

    /**
     * Scope a query to appointments overlapping the given time window.
     *
     * Boundary-touching windows (end == start) do NOT overlap.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->where('start_time', '<', $end)->where('end_time', '>', $start);
    }

    /**
     * Determine whether the appointment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::Cancelled;
    }
}
