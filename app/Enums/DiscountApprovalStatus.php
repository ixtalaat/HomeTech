<?php

namespace App\Enums;

enum DiscountApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('statuses.pending'),
            self::Approved => __('statuses.approved'),
            self::Rejected => __('statuses.rejected'),
        };
    }
}
