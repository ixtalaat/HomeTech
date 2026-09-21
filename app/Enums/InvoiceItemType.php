<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Service = 'service';
    case Labor = 'labor';
    case Material = 'material';
    case Additional = 'additional';
    case Fee = 'fee';

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Service => 'Service',
            self::Labor => 'Labor',
            self::Material => 'Material',
            self::Additional => 'Additional Work',
            self::Fee => 'Fee',
        };
    }
}
