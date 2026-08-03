<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\SkillLevelEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    use HasParentStates;

    protected $model = StudentEnrollment::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'skill_level' => SkillLevelEnum::Beginner->value,
            'status' => EnrollmentStatusEnum::Active->value,
            'started_at' => today()->toDateString(),
            'ended_at' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'instrument_id' => Instrument::factory(),
        ];
    }

    /** Status state: `active`. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatusEnum::Active->value,
            'ended_at' => null,
        ]);
    }

    /** Status state: `paused`. */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatusEnum::Paused->value,
            'ended_at' => null,
        ]);
    }

    /** Status state: `completed`. */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatusEnum::Completed->value,
            'ended_at' => today()->toDateString(),
        ]);
    }

    /** Status state: `cancelled`. */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatusEnum::Cancelled->value,
            'ended_at' => today()->toDateString(),
        ]);
    }

    /**
     * Enrollment with one class session, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (StudentEnrollment $enrollment): void {
            ClassSession::factory()->create(['enrollment_id' => $enrollment->id]);
        });
    }

    /** Enrollment without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (StudentEnrollment $enrollment): void {
            $enrollment->teacher->instruments()->syncWithoutDetaching([
                $enrollment->instrument_id => [
                    'skill_level' => $enrollment->skill_level->value,
                    'is_primary' => false,
                ],
            ]);
        });
    }
}
