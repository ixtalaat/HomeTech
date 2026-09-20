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
            self::PendingReview => 'Pending Review',
            self::InfoRequested => 'Info Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::TechnicianAssigned => 'Technician Assigned',
            self::Scheduled => 'Scheduled',
            self::TechnicianOnWay => 'Technician On The Way',
            self::InProgress => 'In Progress',
            self::WaitingCustomerApproval => 'Waiting Customer Approval',
            self::Completed => 'Completed',
            self::Invoiced => 'Invoiced',
            self::Paid => 'Paid',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
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
