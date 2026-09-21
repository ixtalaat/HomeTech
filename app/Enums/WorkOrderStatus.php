<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => __('statuses.open'),
            self::InProgress => __('statuses.in_progress'),
            self::Completed => __('statuses.completed'),
        };
    }
}
