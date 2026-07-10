<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN       = 'admin';
    case TEACHER     = 'teacher';
    case STUDENT     = 'student';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Ordered hierarchy — higher index = higher authority. */
    public function hierarchyLevel(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 3,
            self::ADMIN       => 2,
            self::TEACHER     => 1,
            self::STUDENT     => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'سوپر ادمین',
            self::ADMIN       => 'مدیر',
            self::TEACHER     => 'استاد',
            self::STUDENT     => 'هنرجو',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'purple',
            self::ADMIN       => 'amber',
            self::TEACHER     => 'blue',
            self::STUDENT     => 'green',
        };
    }

    /** Returns true if this role can manage the given role. */
    public function canManage(self $target): bool
    {
        return $this->hierarchyLevel() > $target->hierarchyLevel();
    }
}
