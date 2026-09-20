<?php

namespace App\Models;

use App\Enums\AdditionalWorkStatus;
use Database\Factories\AdditionalWorkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalWork extends Model
{
    /** @use HasFactory<AdditionalWorkFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'additional_work';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'work_order_id',
        'description',
        'cost',
        'status',
        'requested_by',
        'decided_by',
        'decided_at',
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
            'cost' => 'decimal:2',
            'status' => AdditionalWorkStatus::class,
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the work order for the additional work.
     *
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the technician who requested the work.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the customer who decided on the work.
     *
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Scope a query to billable items only (approved or performed).
     *
     * Rejected and pending items are never billable (BR-005).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('status', [AdditionalWorkStatus::Approved, AdditionalWorkStatus::Completed]);
    }

    /**
     * Scope a query to items awaiting customer decision.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AdditionalWorkStatus::PendingApproval);
    }

    /**
     * Determine whether the item is awaiting a customer decision.
     */
    public function isPending(): bool
    {
        return $this->status === AdditionalWorkStatus::PendingApproval;
    }
}
