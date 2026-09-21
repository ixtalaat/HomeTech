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
            self::Service => __('statuses.service'),
            self::Labor => __('statuses.labor'),
            self::Material => __('statuses.material'),
            self::Additional => __('statuses.additional_work'),
            self::Fee => __('statuses.fee'),
        };
    }
}
