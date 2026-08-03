<?php

namespace Database\Factories;

use App\Enums\TeacherStatusEnum;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'status' => TeacherStatusEnum::Active,
            'bio' => $this->faker->text(),
            'hire_date' => $this->faker->date(),
        ];
    }

    /** Status state: `active`. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeacherStatusEnum::Active->value,
        ]);
    }

    /** Status state: `inactive`. */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeacherStatusEnum::Inactive->value,
        ]);
    }

    /**
     * Teacher with one enrollment, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (Teacher $teacher): void {
            StudentEnrollment::factory()->create(['teacher_id' => $teacher->id]);
        });
    }

    /** Teacher without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
