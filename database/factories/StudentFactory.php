<?php

namespace Database\Factories;

use App\Enums\StudentStatusEnum;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'full_name' => 'Student ' . self::$sequence,
            'phone' => '0912' . str_pad((string) self::$sequence, 7, '0', STR_PAD_LEFT),
            'parent_phone' => null,
            'status' => StudentStatusEnum::Active->value,
            'join_date' => today()->toDateString(),
            'notes' => null,
        ];
    }

    /** Status state: `active`. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatusEnum::Active->value,
        ]);
    }

    /** Status state: `paused`. */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatusEnum::Paused->value,
        ]);
    }

    /** Status state: `inactive`. */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatusEnum::Inactive->value,
        ]);
    }

    /** Status state: `graduated`. */
    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatusEnum::Graduated->value,
        ]);
    }

    /**
     * Student with one enrollment, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (Student $student): void {
            StudentEnrollment::factory()->create(['student_id' => $student->id]);
        });
    }

    /** Student without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
