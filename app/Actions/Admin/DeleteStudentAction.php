<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Student;

/** Permanently deletes a student only when no protected dependency exists. */
final class DeleteStudentAction
{
    public function __construct(?ProtectedDependencyChecker $dependencies = null)
    {
        $this->dependencies = $dependencies ?? new ProtectedDependencyChecker();
    }

    private readonly ProtectedDependencyChecker $dependencies;

    public function delete(Student $student): bool
    {
        if ($this->dependencies->hasProtectedDependency($student)) {
            return false;
        }

        return (bool) $student->delete();
    }

    public function execute(Student $student): bool
    {
        return $this->delete($student);
    }
}
