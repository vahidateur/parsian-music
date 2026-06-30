<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
