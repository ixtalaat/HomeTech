<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('statuses.scheduled'),
            self::Confirmed => __('statuses.confirmed'),
            self::Rescheduled => __('statuses.rescheduled'),
            self::Cancelled => __('statuses.cancelled'),
            self::Completed => __('statuses.completed'),
        };
    }
}
