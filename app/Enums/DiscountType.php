<?php

namespace App\Enums;

enum DiscountType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Amount',
            self::Percent => 'Percentage',
        };
    }
}
