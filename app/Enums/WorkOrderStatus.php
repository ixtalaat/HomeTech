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
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }
}
