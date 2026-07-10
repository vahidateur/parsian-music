<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case Draft          = 'draft';
    case Issued         = 'issued';
    case PartiallyPaid  = 'partially_paid';
    case Paid           = 'paid';
    case Overdue        = 'overdue';
    case Cancelled      = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft         => 'پیش‌نویس',
            self::Issued        => 'صادر شده',
            self::PartiallyPaid => 'پرداخت جزئی',
            self::Paid          => 'پرداخت شده',
            self::Overdue       => 'سررسید گذشته',
            self::Cancelled     => 'لغو شده',
        };
    }

    /** Tailwind color token for badges. */
    public function color(): string
    {
        return match ($this) {
            self::Draft         => 'gray',
            self::Issued        => 'blue',
            self::PartiallyPaid => 'amber',
            self::Paid          => 'emerald',
            self::Overdue       => 'red',
            self::Cancelled     => 'rose',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft         => in_array($next, [self::Issued, self::Cancelled]),
            self::Issued        => in_array($next, [self::PartiallyPaid, self::Paid, self::Overdue, self::Cancelled]),
            self::PartiallyPaid => in_array($next, [self::Paid, self::Overdue, self::Cancelled]),
            self::Overdue       => in_array($next, [self::PartiallyPaid, self::Paid, self::Cancelled]),
            self::Paid, self::Cancelled => false,
        };
    }
}
