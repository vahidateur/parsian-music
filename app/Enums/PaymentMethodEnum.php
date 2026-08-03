<?php

namespace App\Enums;

/**
 * Method used to settle an invoice payment.
 *
 * Single source of truth for payment method values across the billing domain.
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

    public function label(): string
    {
        return match ($this) {
            self::Cash         => 'نقدی',
            self::Card         => 'کارت به کارت',
            self::BankTransfer => 'انتقال بانکی',
        };
    }

    /** Tailwind color token for badges. */
    public function color(): string
    {
        return match ($this) {
            self::Cash         => 'emerald',
            self::Card         => 'sky',
            self::BankTransfer => 'violet',
        };
    }
}
