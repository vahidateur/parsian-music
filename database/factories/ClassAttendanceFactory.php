<?php

namespace Database\Factories;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\User;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassAttendance>
 */
class ClassAttendanceFactory extends Factory
{
    use HasParentStates;

    protected $model = ClassAttendance::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'status' => AttendanceStatusEnum::Present->value,
            'note' => null,
            'marked_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'student_id' => static fn (array $attributes): int => ClassSession::query()
                ->findOrFail($attributes['class_session_id'])
                ->student_id,
            'marked_by' => User::factory(),
        ];
    }

    /** Status state: `present`. */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Present->value,
        ]);
    }

    /** Status state: `absent`. */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Absent->value,
        ]);
    }

    /** Status state: `late`. */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Late->value,
        ]);
    }

    /** Status state: `excused`. */
    public function excused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Excused->value,
        ]);
    }
}
