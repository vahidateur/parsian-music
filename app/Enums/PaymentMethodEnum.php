<?php

namespace App\Enums;

/**
 * Method used to make a Payment.
 *
 * Single source of truth for payment method values across the PaymentModule.
 */
enum PaymentMethodEnum: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    /**
     * All valid payment method values for validation.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
