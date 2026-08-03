<?php

namespace App\Enums;

enum LeadStatusEnum: string
{
    case New             = 'new';
    case Contacted       = 'contacted';
    case Interested      = 'interested';
    case TrialScheduled  = 'trial_scheduled';
    case Registered      = 'registered';
    case Lost            = 'lost';

    /**
     * All valid status values for validation.
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
            self::New            => 'جدید',
            self::Contacted      => 'تماس گرفته شده',
            self::Interested     => 'علاقه‌مند',
            self::TrialScheduled => 'جلسه آزمایشی',
            self::Registered     => 'ثبت‌نام شده',
            self::Lost           => 'از دست رفته',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New            => 'sky',
            self::Contacted      => 'blue',
            self::Interested     => 'violet',
            self::TrialScheduled => 'amber',
            self::Registered     => 'emerald',
            self::Lost           => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Registered, self::Lost], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::New            => in_array($next, [self::Contacted, self::Lost]),
            self::Contacted      => in_array($next, [self::Interested, self::Lost]),
            self::Interested     => in_array($next, [self::TrialScheduled, self::Lost]),
            self::TrialScheduled => in_array($next, [self::Registered, self::Lost]),
            self::Registered, self::Lost => false,
        };
    }
}
