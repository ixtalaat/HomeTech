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
            self::Purchase => 'Purchase',
            self::Adjustment => 'Adjustment',
            self::Consumption => 'Consumption',
            self::Return => 'Return',
            self::Reversal => 'Reversal',
        };
    }
}
