<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Teacher;

/** Permanently deletes a teacher only when no protected dependency exists. */
final class DeleteTeacherAction
{
    public function __construct(?ProtectedDependencyChecker $dependencies = null)
    {
        $this->dependencies = $dependencies ?? new ProtectedDependencyChecker();
    }

    private readonly ProtectedDependencyChecker $dependencies;

    public function delete(Teacher $teacher): bool
    {
        if ($this->dependencies->hasProtectedDependency($teacher)) {
            return false;
        }

        return (bool) $teacher->delete();
    }

    public function execute(Teacher $teacher): bool
    {
        return $this->delete($teacher);
    }
}
