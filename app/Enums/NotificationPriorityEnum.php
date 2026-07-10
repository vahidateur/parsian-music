<?php

namespace App\Enums;

enum NotificationPriorityEnum: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High   => 'بالا',
            self::Medium => 'متوسط',
            self::Low    => 'پایین',
        };
    }

    /** Delay (seconds) before the queued notification job runs. */
    public function queueDelay(): int
    {
        return match ($this) {
            self::High   => 0,
            self::Medium => 30,
            self::Low    => 120,
        };
    }
}
