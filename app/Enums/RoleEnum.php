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

    /**
     * Roles this role may assign to another account.
     *
     * Keeps the privilege-escalation boundary in the domain layer so no
     * controller body or Blade template has to compare roles itself.
     *
     * @return array<int, self>
     */
    public function assignableRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $target): bool => $this->canManage($target)
        ));
    }

    /**
     * Values of `assignableRoles()`, ready for a validation `in:` rule.
     *
     * @return array<int, string>
     */
    public function assignableRoleValues(): array
    {
        return array_map(fn (self $role): string => $role->value, $this->assignableRoles());
    }
}
