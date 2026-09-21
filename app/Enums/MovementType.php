<?php

namespace App\Enums;

enum MovementType: string
{
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Consumption = 'consumption';
    case Return = 'return';
    case Reversal = 'reversal';

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Purchase => __('statuses.purchase'),
            self::Adjustment => __('statuses.adjustment'),
            self::Consumption => __('statuses.consumption'),
            self::Return => __('statuses.return'),
            self::Reversal => __('statuses.reversal'),
        };
    }
}
