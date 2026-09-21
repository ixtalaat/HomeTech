<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    /**
     * Get a human-readable label for the method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => __('statuses.cash'),
            self::Card => __('statuses.card'),
            self::BankTransfer => __('statuses.bank_transfer'),
        };
    }
}
