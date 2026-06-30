<?php

namespace App\Enums;

/**
 * Canonical skill level for teacher-instrument assignments and enrollments.
 *
 * Single source of truth for skill level values across controllers.
 */
enum SkillLevelEnum: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    /**
     * All valid skill level values for validation.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
