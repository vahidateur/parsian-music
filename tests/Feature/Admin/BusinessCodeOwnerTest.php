<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\StudentAction;
use App\Actions\Admin\TeacherAction;
use App\Domain\Scheduling\BusinessCodeOwner;
use App\DTOs\ConvertLeadData;
use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Lead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Focused persistence coverage for immutable operational-code ownership.
 *
 * Validates: Requirements 8.1-8.5, 16.3-16.5, 21.1.
 */
class BusinessCodeOwnerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    public function test_actions_allocate_unique_non_empty_canonical_codes(): void
    {
        $teacher = app(TeacherAction::class)->create($this->teacherPayload(1));
        $student = app(StudentAction::class)->create($this->studentPayload(1));
        $converted = app(LeadService::class)->convert(
            Lead::factory()->create(['status' => LeadStatusEnum::TrialScheduled]),
            new ConvertLeadData,
        );

        $this->assertMatchesRegularExpression('/^T-\d{5,}$/', $teacher->teacher_code);
        $this->assertMatchesRegularExpression('/^S-\d{5,}$/', $student->student_code);
        $this->assertMatchesRegularExpression('/^S-\d{5,}$/', (string) $converted->student_code);
        $this->assertNotSame($student->student_code, $converted->student_code);
    }

    public function test_user_payloads_cannot_set_or_replace_codes(): void
    {
        $teacher = app(TeacherAction::class)->create($this->teacherPayload(2));
        $student = app(StudentAction::class)->create($this->studentPayload(2));

        $this->actingAs($this->admin)
            ->put(route('admin.teachers.update', $teacher), [
                ...$this->teacherPayload(2),
                'teacher_code' => 'T-ATTEMPT',
            ])
            ->assertSessionHasErrors('teacher_code');

        $this->actingAs($this->admin)
            ->put(route('admin.students.update', $student), [
                ...$this->studentPayload(2),
                'student_code' => '',
            ])
            ->assertSessionHasErrors('student_code');

        $this->assertSame($teacher->teacher_code, $teacher->fresh()->teacher_code);
        $this->assertSame($student->student_code, $student->fresh()->student_code);
    }

    public function test_backfill_updates_only_missing_codes_and_preserves_keys_and_relations(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();
        $instrument = Instrument::factory()->create();
        $teacher->instruments()->attach($instrument, ['skill_level' => 'beginner', 'is_primary' => true]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);

        DB::table('teachers')->where('id', $teacher->id)->update(['teacher_code' => '']);
        DB::table('students')->where('id', $student->id)->update(['student_code' => '']);

        $owner = app(BusinessCodeOwner::class);
        $backfilledTeacher = $owner->backfillTeacher($teacher->fresh());
        $backfilledStudent = $owner->backfillStudent($student->fresh());

        $this->assertSame($teacher->id, $backfilledTeacher->id);
        $this->assertSame($student->id, $backfilledStudent->id);
        $this->assertMatchesRegularExpression('/^T-\d{5,}$/', $backfilledTeacher->teacher_code);
        $this->assertMatchesRegularExpression('/^S-\d{5,}$/', $backfilledStudent->student_code);
        $this->assertDatabaseHas('teacher_instruments', [
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function teacherPayload(int $number): array
    {
        return [
            'full_name' => "Teacher {$number}",
            'phone' => '0912111'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
        ];
    }

    /** @return array<string, mixed> */
    private function studentPayload(int $number): array
    {
        return [
            'full_name' => "Student {$number}",
            'phone' => '0912222'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'join_date' => now()->toDateString(),
        ];
    }
}
