<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'maintenance_request_id',
        'user_id',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total',
        'paid_amount',
        'status',
        'issued_at',
        'created_by',
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
            'discount_type' => DiscountType::class,
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    /**
     * Get the maintenance request for the invoice.
     *
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    /**
     * Get the customer billed by the invoice.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the line items of the invoice.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments made against the invoice.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the staff member who created the invoice.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to invoices owned by the given user.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to unpaid invoices (issued or partially paid).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid]);
    }

    /**
     * Get the remaining balance.
     */
    public function remaining(): float
    {
        return round((float) $this->total - (float) $this->paid_amount, 2);
    }

    /**
     * Determine whether the invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->remaining() <= 0 && (float) $this->total > 0;
    }

    /**
     * Determine whether the invoice accepts payments.
     */
    public function acceptsPayments(): bool
    {
        return in_array($this->status, [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid], true);
    }

    /**
     * Determine whether the invoice belongs to the given user.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
