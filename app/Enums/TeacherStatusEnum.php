<?php

namespace App\Enums;

/**
 * Canonical status for a Teacher record.
 *
 * Single source of truth for teacher active/inactive state.
 * Distinct from SessionStatusEnum and EnrollmentStatusEnum.
 */
enum TeacherStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

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
