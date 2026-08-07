<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttendanceStudentScopeRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_record_attendance_for_an_unrelated_existing_student(): void
    {
        $admin = User::factory()->admin()->create();
        $session = ClassSession::factory()->create();
        $unrelatedStudent = Student::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.sessions.attendance.store', $session), [
                'student_id' => $unrelatedStudent->id,
                'status' => AttendanceStatusEnum::Present->value,
                'note' => 'Injected attendance',
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('class_attendances', [
            'class_session_id' => $session->id,
            'student_id' => $unrelatedStudent->id,
        ]);
    }

    public function test_owning_teacher_cannot_record_attendance_for_an_unrelated_existing_student(): void
    {
        $teacherUser = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $enrollment = StudentEnrollment::factory()->create(['teacher_id' => $teacher->id]);
        $session = ClassSession::factory()->create(['enrollment_id' => $enrollment->id]);
        $unrelatedStudent = Student::factory()->create();

        $this->actingAs($teacherUser)
            ->post(route('teacher.attendance.save', $session), [
                'attendance' => [[
                    'student_id' => $unrelatedStudent->id,
                    'status' => AttendanceStatusEnum::Present->value,
                    'note' => 'Injected attendance',
                ]],
            ])
            ->assertSessionHasErrors('attendance.0.student_id');

        $this->assertDatabaseMissing('class_attendances', [
            'class_session_id' => $session->id,
            'student_id' => $unrelatedStudent->id,
        ]);
    }

    public function test_admin_can_record_attendance_for_a_direct_session_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $session = ClassSession::factory()->direct([
            'student_id' => $student->id,
            'teacher_id' => Teacher::factory()->create()->id,
            'instrument_id' => Instrument::factory()->create()->id,
        ])->create();

        $this->actingAs($admin)
            ->post(route('admin.sessions.attendance.store', $session), [
                'student_id' => $student->id,
                'status' => AttendanceStatusEnum::Present->value,
                'note' => 'Direct session attendance',
            ])
            ->assertRedirect(route('admin.sessions.attendance.show', $session));

        $this->assertDatabaseHas('class_attendances', [
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => AttendanceStatusEnum::Present->value,
            'note' => 'Direct session attendance',
            'marked_by' => $admin->id,
        ]);
    }

    public function test_owning_teacher_can_record_attendance_for_an_enrollment_student(): void
    {
        $teacherUser = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $enrollment = StudentEnrollment::factory()->create(['teacher_id' => $teacher->id]);
        $session = ClassSession::factory()->create(['enrollment_id' => $enrollment->id]);

        $this->actingAs($teacherUser)
            ->post(route('teacher.attendance.save', $session), [
                'attendance' => [[
                    'student_id' => $enrollment->student_id,
                    'status' => AttendanceStatusEnum::Late->value,
                    'note' => 'Enrollment session attendance',
                ]],
            ])
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertDatabaseHas('class_attendances', [
            'class_session_id' => $session->id,
            'student_id' => $enrollment->student_id,
            'status' => AttendanceStatusEnum::Late->value,
            'note' => 'Enrollment session attendance',
            'marked_by' => $teacherUser->id,
        ]);
    }
}
