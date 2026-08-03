<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;

/**
 * Database-backed graphs for admin bulk and real-session integration tests.
 *
 * The helpers always create persisted records through the application factories;
 * no production defaults or schema changes are involved.
 */
final class AdminBulkFixtures
{
    public static function teacher(array $attributes = []): Teacher
    {
        return Teacher::factory()->create($attributes);
    }

    public static function student(array $attributes = []): Student
    {
        return Student::factory()->create($attributes);
    }

    public static function enrollment(
        ?Student $student = null,
        ?Teacher $teacher = null,
        ?Instrument $instrument = null,
        array $attributes = [],
    ): StudentEnrollment {
        return StudentEnrollment::factory()->withParents([
            'student' => $student ?? self::student(),
            'teacher' => $teacher ?? self::teacher(),
            'instrument' => $instrument ?? Instrument::factory()->create(),
        ])->create($attributes);
    }

    public static function enrollmentSession(
        ?StudentEnrollment $enrollment = null,
        array $attributes = [],
    ): ClassSession {
        return ClassSession::factory()
            ->withParents(['enrollment' => $enrollment ?? self::enrollment()])
            ->create($attributes);
    }

    public static function directSession(
        ?Student $student = null,
        ?Teacher $teacher = null,
        ?Instrument $instrument = null,
        array $attributes = [],
    ): ClassSession {
        $student ??= self::student();
        $teacher ??= self::teacher();
        $instrument ??= Instrument::factory()->create();

        return ClassSession::factory()->direct([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->create($attributes);
    }

    /**
     * An enrollment-backed session whose direct tuple differs from enrollment.
     * This is the persisted conflict used by relation-path tests.
     */
    public static function relationConflict(): ClassSession
    {
        $enrollment = self::enrollment();
        $directStudent = self::student();
        $directTeacher = self::teacher();
        $directInstrument = Instrument::factory()->create();

        return ClassSession::factory()
            ->withParents(['enrollment' => $enrollment])
            ->relationConflict([
                'student_id' => $directStudent->id,
                'teacher_id' => $directTeacher->id,
                'instrument_id' => $directInstrument->id,
            ])
            ->create();
    }

    /** Active room suitable for session-input options. */
    public static function activeRoom(string $name = 'Bulk Test Room'): Room
    {
        return Room::factory()->active()->named($name)->create();
    }

    /** Inactive room retained for historical/filter resolution. */
    public static function inactiveRoom(string $name = 'Bulk Historical Room'): Room
    {
        return Room::factory()->inactive()->named($name)->create();
    }

    /** A stable unresolved legacy value is intentionally not persisted as a Room. */
    public static function unresolvedRoomName(): string
    {
        return 'Legacy Bulk Room';
    }

    /**
     * Teacher graph containing enrollment, subscription, invoice, session and
     * attendance dependencies. The returned teacher is never safe to delete.
     */
    public static function protectedTeacher(): Teacher
    {
        $teacher = self::teacher();
        $student = self::student();
        $instrument = Instrument::factory()->create();
        $enrollment = self::enrollment($student, $teacher, $instrument);
        $session = self::enrollmentSession($enrollment);

        Subscription::factory()->withParents([
            'student' => $student,
            'teacher' => $teacher,
            'instrument' => $instrument,
        ])->create();
        Invoice::factory()->create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
        ]);
        ClassAttendance::factory()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
        ]);

        return $teacher->refresh();
    }

    /**
     * Student graph containing every supported protected-dependency category,
     * including a converted lead linked to the returned student.
     */
    public static function protectedStudent(): Student
    {
        $student = self::student();
        $teacher = self::teacher();
        $instrument = Instrument::factory()->create();
        $enrollment = self::enrollment($student, $teacher, $instrument);
        $session = self::enrollmentSession($enrollment);

        Subscription::factory()->withParents([
            'student' => $student,
            'teacher' => $teacher,
            'instrument' => $instrument,
        ])->create();
        Invoice::factory()->create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
        ]);
        ClassAttendance::factory()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
        ]);
        Lead::factory()->create([
            'status' => LeadStatusEnum::Registered->value,
            'converted_student_id' => $student->id,
            'converted_at' => now(),
        ]);

        return $student->refresh();
    }

    /** Actor authorized by TeacherPolicy, StudentPolicy and SessionPolicy. */
    public static function policyActor(): User
    {
        return User::factory()->policyActor()->create(['role' => RoleEnum::ADMIN->value]);
    }

    /** Authenticated actor intentionally denied admin mutations by policy. */
    public static function unauthorizedPolicyActor(): User
    {
        return User::factory()->unauthorizedActor()->create();
    }

    /** Teacher without persisted dependencies, suitable for eligible deletion. */
    public static function eligibleTeacher(): Teacher
    {
        return self::teacher();
    }

    /** Student without persisted dependencies, suitable for eligible deletion. */
    public static function eligibleStudent(): Student
    {
        return self::student();
    }
}
