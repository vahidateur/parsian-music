<?php

namespace App\Enums;

/**
 * Canonical status for a Student record.
 *
 * Single source of truth for student lifecycle state.
 * Distinct from EnrollmentStatusEnum (per-enrollment lifecycle).
 */
enum StudentStatusEnum: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Inactive = 'inactive';
    case Graduated = 'graduated';

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
