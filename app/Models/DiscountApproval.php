<?php

namespace App\Models;

use App\Enums\DiscountApprovalStatus;
use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountApproval extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'discount_type',
        'discount_value',
        'status',
        'requested_by',
        'decided_by',
        'decided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'status' => DiscountApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Get the invoice for the approval request.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the staff member who requested the discount.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the manager who decided.
     *
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Scope a query to pending requests.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DiscountApprovalStatus::Pending);
    }

    /**
     * Determine whether the request is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === DiscountApprovalStatus::Pending;
    }
}
