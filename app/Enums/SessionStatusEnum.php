<?php

namespace App\Enums;

/**
 * Canonical lifecycle status for a ClassSession.
 *
 * SINGLE SOURCE OF TRUTH POLICY:
 * - This enum owns session lifecycle semantics ONLY (scheduled → completed/
 *   cancelled/missed).
 * - Attendance state (present/absent/late/excused) lives in
 *   AttendanceStatusEnum — never here.
 * - No controller, report, or service may use raw strings for session status.
 * - All CASE WHEN / WHERE clauses must reference ->value from this enum.
 */
enum SessionStatusEnum: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Missed = 'missed';

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
