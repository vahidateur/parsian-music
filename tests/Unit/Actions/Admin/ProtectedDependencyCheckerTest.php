<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Admin;

use App\Actions\Admin\ProtectedDependencyChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

final class ProtectedDependencyCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_student_dependency_graphs_are_protected_without_mutation(): void
    {
        $teacher = AdminBulkFixtures::protectedTeacher();
        $student = AdminBulkFixtures::protectedStudent();
        $checker = new ProtectedDependencyChecker();

        $teacherCategories = $checker->categories($teacher);
        $studentCategories = $checker->categories($student);

        $this->assertContains('enrollment', $teacherCategories);
        $this->assertContains('subscription', $teacherCategories);
        $this->assertContains('invoice', $teacherCategories);
        $this->assertContains('class_session', $teacherCategories);
        $this->assertContains('attendance', $teacherCategories);
        $this->assertContains('converted_lead', $studentCategories);
        $this->assertTrue($checker->hasProtectedDependency($teacher));
        $this->assertTrue($checker->check($student));
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_independent_records_have_no_protected_dependency(): void
    {
        $teacher = AdminBulkFixtures::eligibleTeacher();
        $student = AdminBulkFixtures::eligibleStudent();
        $checker = new ProtectedDependencyChecker();

        $this->assertSame([], $checker->categories($teacher));
        $this->assertSame([], $checker->categories($student));
        $this->assertFalse($checker->hasProtectedDependency($teacher));
        $this->assertFalse($checker->check($student));
    }
}
