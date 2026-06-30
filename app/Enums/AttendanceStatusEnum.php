<?php

namespace App\Enums;

/**
 * Canonical attendance status for a student in a ClassSession.
 *
 * SINGLE SOURCE OF TRUTH POLICY:
 * - This enum owns student presence semantics ONLY.
 * - A session can be "completed" (SessionStatusEnum) while a student's
 *   attendance is "absent" — these are independent concepts.
 * - No controller or report may use raw strings for attendance status.
 * - All CASE WHEN / WHERE clauses must reference ->value from this enum.
 */
enum AttendanceStatusEnum: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

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
