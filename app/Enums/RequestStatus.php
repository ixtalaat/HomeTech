<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PendingReview = 'pending_review';
    case InfoRequested = 'info_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case TechnicianAssigned = 'technician_assigned';
    case Scheduled = 'scheduled';
    case TechnicianOnWay = 'technician_on_way';
    case InProgress = 'in_progress';
    case WaitingCustomerApproval = 'waiting_customer_approval';
    case Completed = 'completed';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingReview => __('statuses.pending_review'),
            self::InfoRequested => __('statuses.info_requested'),
            self::Approved => __('statuses.approved'),
            self::Rejected => __('statuses.rejected'),
            self::TechnicianAssigned => __('statuses.technician_assigned'),
            self::Scheduled => __('statuses.scheduled'),
            self::TechnicianOnWay => __('statuses.technician_on_way'),
            self::InProgress => __('statuses.in_progress'),
            self::WaitingCustomerApproval => __('statuses.waiting_customer_approval'),
            self::Completed => __('statuses.completed'),
            self::Invoiced => __('statuses.invoiced'),
            self::Paid => __('statuses.paid'),
            self::Closed => __('statuses.closed'),
            self::Cancelled => __('statuses.cancelled'),
        };
    }

    /**
     * Determine whether the status is terminal (no outgoing transitions).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Closed, self::Cancelled], true);
    }
}
