<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Failed    = 'failed';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'در انتظار',
            self::Completed => 'تکمیل شده',
            self::Failed    => 'ناموفق',
            self::Refunded  => 'بازگشت وجه',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'amber',
            self::Completed => 'emerald',
            self::Failed    => 'red',
            self::Refunded  => 'violet',
        };
    }

    /** Only completed payments count toward the invoice balance. */
    public function countsTowardBalance(): bool
    {
        return $this === self::Completed;
    }
}
