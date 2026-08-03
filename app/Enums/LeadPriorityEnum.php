<?php

namespace App\Enums;

enum LeadPriorityEnum: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';

    /**
     * All valid priority values for validation.
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
            self::High   => 'بالا',
            self::Medium => 'متوسط',
            self::Low    => 'پایین',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::High   => 'red',
            self::Medium => 'amber',
            self::Low    => 'gray',
        };
    }

    /** Follow-up reminder threshold in days. */
    public function followUpDays(): int
    {
        return match ($this) {
            self::High   => 1,
            self::Medium => 3,
            self::Low    => 7,
        };
    }
}
