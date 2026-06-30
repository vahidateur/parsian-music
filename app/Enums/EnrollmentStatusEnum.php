<?php

namespace App\Enums;

/**
 * Canonical lifecycle status for a StudentEnrollment.
 *
 * Single source of truth for enrollment state across all controllers.
 * Distinct from SessionStatusEnum (session lifecycle) and
 * AttendanceStatusEnum (student presence per session).
 */
enum EnrollmentStatusEnum: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * All valid status values for validation.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
