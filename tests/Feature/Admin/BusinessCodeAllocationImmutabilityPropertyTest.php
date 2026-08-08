<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\StudentAction;
use App\Actions\Admin\TeacherAction;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;

/**
 * Feature: interactive-session-scheduling, Property 9: BusinessCode uniqueness and immutability invariant.
 *
 * **Validates: Requirements 8.1, 8.2, 8.3, 8.4**
 */
final class BusinessCodeAllocationImmutabilityPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_codes_are_unique_and_immutable_for_one_hundred_deterministic_sequences(): void
    {
        $cases = DeterministicSchedulingCases::relationPaths();
        $this->assertCount(
            DeterministicSchedulingCases::MINIMUM_CASES,
            $cases,
            DeterministicSchedulingCases::firstFailure('Property 9/case generation', DeterministicSchedulingCases::MINIMUM_CASES, count($cases)),
        );
        $teachers = app(TeacherAction::class);
        $students = app(StudentAction::class);
        $teacherCodes = [];
        $studentCodes = [];

        foreach ($cases as $case) {
            $teacher = $teachers->create($this->teacherPayload($case));
            $student = $students->create($this->studentPayload($case));
            $backfillTeacher = Teacher::factory()->create(['teacher_code' => sprintf('T-9%04d', $case['case'])]);
            $backfillStudent = Student::factory()->create(['student_code' => sprintf('S-9%04d', $case['case'])]);
            DB::table('teachers')->where('id', $backfillTeacher->id)->update(['teacher_code' => '']);
            DB::table('students')->where('id', $backfillStudent->id)->update(['student_code' => '']);
            $backfillTeacher = $teachers->backfill($backfillTeacher->fresh());
            $backfillStudent = $students->backfill($backfillStudent->fresh());
            $this->assertCanonical($case, 'creation/teacher', 'T', $teacher->teacher_code);
            $this->assertCanonical($case, 'creation/student', 'S', $student->student_code);
            $this->assertCanonical($case, 'backfill/teacher', 'T', $backfillTeacher->teacher_code);
            $this->assertCanonical($case, 'backfill/student', 'S', $backfillStudent->student_code);

            $this->assertSame($backfillTeacher->teacher_code, $teachers->backfill($backfillTeacher)->teacher_code, $this->diagnostic($case, 'backfill/teacher allocated once', $backfillTeacher->teacher_code, $teachers->backfill($backfillTeacher)->teacher_code));
            $this->assertSame($backfillStudent->student_code, $students->backfill($backfillStudent)->student_code, $this->diagnostic($case, 'backfill/student allocated once', $backfillStudent->student_code, $students->backfill($backfillStudent)->student_code));
            $teacherCodes = [...$teacherCodes, $teacher->teacher_code, $backfillTeacher->teacher_code];
            $studentCodes = [...$studentCodes, $student->student_code, $backfillStudent->student_code];
            $this->assertSame(count($teacherCodes), count(array_unique($teacherCodes)), $this->diagnostic($case, 'teacher namespace uniqueness', count($teacherCodes), count(array_unique($teacherCodes))));
            $this->assertSame(count($studentCodes), count(array_unique($studentCodes)), $this->diagnostic($case, 'student namespace uniqueness', count($studentCodes), count(array_unique($studentCodes))));

            $teacherCode = $teacher->teacher_code;
            $studentCode = $student->student_code;
            $teachers->update($teacher, $this->teacherPayload($case, 'ordinary'));
            $students->update($student, $this->studentPayload($case, 'ordinary'));
            $this->assertSame($teacherCode, $teacher->fresh()->teacher_code, $this->diagnostic($case, 'ordinary teacher update', $teacherCode, $teacher->fresh()->teacher_code));
            $this->assertSame($studentCode, $student->fresh()->student_code, $this->diagnostic($case, 'ordinary student update', $studentCode, $student->fresh()->student_code));

            foreach (['set' => "T-SET-{$case['case']}", 'replace' => "T-REPLACE-{$case['case']}", 'clear' => ''] as $operation => $value) {
                $this->assertForbiddenUpdate($case, $operation, $teachers, $teacher, 'teacher_code', $value, $teacherCode);
                $this->assertForbiddenUpdate($case, $operation, $students, $student, 'student_code', str_replace('T-', 'S-', $value), $studentCode);
            }
        }
    }

    /** @param array<string, mixed> $case @return array<string, string> */
    private function teacherPayload(array $case, string $phase = 'create'): array
    {
        return ['full_name' => "Teacher {$phase} {$case['case']}", 'phone' => '091211'.str_pad((string) $case['case'], 4, '0', STR_PAD_LEFT)];
    }

    /** @param array<string, mixed> $case @return array<string, string> */
    private function studentPayload(array $case, string $phase = 'create'): array
    {
        return ['full_name' => "Student {$phase} {$case['case']}", 'phone' => '091222'.str_pad((string) $case['case'], 4, '0', STR_PAD_LEFT), 'join_date' => '2026-08-04'];
    }

    /** @param array<string, mixed> $case */
    private function assertCanonical(array $case, string $boundary, string $prefix, mixed $code): void
    {
        $this->assertSame(1, preg_match('/^'.$prefix.'-\d{5,}$/', (string) $code), $this->diagnostic($case, $boundary, "non-empty {$prefix} canonical code", $code));
    }

    /** @param array<string, mixed> $case */
    private function assertForbiddenUpdate(array $case, string $operation, object $action, Teacher|Student $record, string $column, string $attempted, string $persisted): void
    {
        try {
            $payload = $column === 'teacher_code' ? $this->teacherPayload($case, "forbidden {$operation}") : $this->studentPayload($case, "forbidden {$operation}");
            $action->update($record, [...$payload, $column => $attempted]);
            $errors = [];
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        }

        $this->assertArrayHasKey($column, $errors, $this->diagnostic($case, "forbidden {$column}/{$operation} rejection", [$column => 'validation error'], $errors));
        $observed = (string) $record->fresh()->getAttribute($column);
        $this->assertSame($persisted, $observed, $this->diagnostic($case, "forbidden {$column}/{$operation} preservation", $persisted, $observed));
    }

    /** @param array<string, mixed> $case */
    private function diagnostic(array $case, string $boundary, mixed $expected, mixed $observed): string
    {
        return DeterministicSchedulingCases::firstFailure(
            "Property 9/{$boundary}; seed={$case['seed']}; case={$case['case']}",
            $expected,
            $observed,
        );
    }
}
