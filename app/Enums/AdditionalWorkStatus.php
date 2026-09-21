<?php

namespace App\Enums;

enum AdditionalWorkStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => __('statuses.pending_approval'),
            self::Approved => __('statuses.approved'),
            self::Rejected => __('statuses.rejected'),
            self::Completed => __('statuses.completed'),
        };
    }
}
