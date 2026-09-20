<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * Note: controllers must never assign `status` from user input;
     * status changes go through RequestStatusService.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'service_id',
        'address_id',
        'technician_id',
        'description',
        'preferred_date',
        'preferred_time',
        'photos',
        'status',
        'rejection_reason',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'photos' => 'array',
            'preferred_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the requested service.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the service address for the request.
     *
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get the technician assigned to the request.
     *
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Get the appointment booked for the request.
     *
     * @return HasOne<Appointment, $this>
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    /**
     * Get the work order for the request.
     *
     * @return HasOne<WorkOrder, $this>
     */
    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    /**
     * Get the staff member who reviewed the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the status history of the request.
     *
     * @return HasMany<MaintenanceRequestStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(MaintenanceRequestStatusHistory::class)->oldest();
    }

    /**
     * Scope a query to only include requests owned by the given user.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter requests by status.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeByStatus(Builder $query, RequestStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Determine whether the request belongs to the given user.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Determine whether the request can still be reviewed by staff.
     */
    public function isReviewable(): bool
    {
        return in_array($this->status, [RequestStatus::PendingReview, RequestStatus::InfoRequested], true);
    }
}
