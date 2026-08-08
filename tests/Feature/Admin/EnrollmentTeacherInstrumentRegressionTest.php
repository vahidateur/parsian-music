<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\EnrollmentStatusEnum;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EnrollmentTeacherInstrumentRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_an_unqualified_teacher_to_an_enrollment(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = StudentEnrollment::factory()->create();
        $unqualifiedTeacher = Teacher::factory()->independent()->create();

        $this->actingAs($admin)
            ->put(route('admin.enrollments.update', $enrollment), [
                'teacher_id' => $unqualifiedTeacher->id,
                'status' => EnrollmentStatusEnum::Active->value,
                'notes' => 'Invalid reassignment',
            ])
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame($enrollment->teacher_id, $enrollment->refresh()->teacher_id);
    }

    public function test_admin_can_assign_a_teacher_qualified_for_the_enrollment_instrument(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = StudentEnrollment::factory()->create();
        $qualifiedTeacher = Teacher::factory()->independent()->create();
        $qualifiedTeacher->instruments()->syncWithoutDetaching([
            $enrollment->instrument_id => ['skill_level' => 'beginner', 'is_primary' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.enrollments.update', $enrollment), [
                'teacher_id' => $qualifiedTeacher->id,
                'status' => EnrollmentStatusEnum::Active->value,
            ])
            ->assertRedirect(route('admin.enrollments.index'));

        $this->assertSame($qualifiedTeacher->id, $enrollment->refresh()->teacher_id);
    }
}
