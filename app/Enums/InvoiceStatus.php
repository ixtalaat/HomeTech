<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('statuses.draft'),
            self::Issued => __('statuses.issued'),
            self::PartiallyPaid => __('statuses.partially_paid'),
            self::Paid => __('statuses.paid'),
            self::Cancelled => __('statuses.cancelled'),
        };
    }
}
