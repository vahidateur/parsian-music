<?php

namespace Database\Factories;

use App\Enums\TeacherStatusEnum;
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
}
