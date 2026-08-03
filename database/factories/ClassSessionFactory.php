<?php

namespace Database\Factories;

use App\Enums\SessionStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\StudentEnrollment;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    use HasParentStates;

    protected $model = ClassSession::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'student_id' => null,
            'teacher_id' => null,
            'instrument_id' => null,
            'recurring_schedule_id' => null,
            'session_date' => today()->toDateString(),
            'start_time' => '15:00:00',
            'duration_minutes' => 60,
            'status' => SessionStatusEnum::Scheduled->value,
            'room' => 'A101',
            'session_fee' => null,
            'discount' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'enrollment_id' => StudentEnrollment::factory(),
        ];
    }

    /** Status state: `scheduled`. */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatusEnum::Scheduled->value,
        ]);
    }

    /** Status state: `completed`. */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatusEnum::Completed->value,
        ]);
    }

    /** Status state: `cancelled`. */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatusEnum::Cancelled->value,
        ]);
    }

    /** Status state: `missed`. */
    public function missed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatusEnum::Missed->value,
        ]);
    }

    /** Session backed only by the persisted direct relation tuple. */
    public function direct(array $relations = []): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_id' => null,
            'student_id' => $relations['student_id'] ?? $attributes['student_id'] ?? null,
            'teacher_id' => $relations['teacher_id'] ?? $attributes['teacher_id'] ?? null,
            'instrument_id' => $relations['instrument_id'] ?? $attributes['instrument_id'] ?? null,
        ]);
    }

    /** Persist a session whose direct tuple intentionally conflicts with enrollment. */
    public function relationConflict(array $relations): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $relations['student_id'],
            'teacher_id' => $relations['teacher_id'],
            'instrument_id' => $relations['instrument_id'],
        ]);
    }

    /**
     * Session with one attendance record, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (ClassSession $session): void {
            ClassAttendance::factory()->create(['class_session_id' => $session->id]);
        });
    }

    /** Session without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ClassSession $session): void {
            $enrollment = $session->enrollment;

            if ($enrollment === null) {
                return;
            }

            $attributes = array_filter([
                'student_id' => $session->student_id ?? $enrollment->student_id,
                'teacher_id' => $session->teacher_id ?? $enrollment->teacher_id,
                'instrument_id' => $session->instrument_id ?? $enrollment->instrument_id,
            ], static fn (mixed $value): bool => $value !== null);

            $session->forceFill($attributes)->saveQuietly();
        });
    }
}
