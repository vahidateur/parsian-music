<?php

namespace Database\Factories;

use App\Enums\SkillLevelEnum;
use App\Models\Instrument;
use App\Models\Teacher;
use App\Models\TeacherInstrument;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherInstrument>
 */
class TeacherInstrumentFactory extends Factory
{
    use HasParentStates;

    protected $model = TeacherInstrument::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'skill_level' => SkillLevelEnum::Beginner->value,
            'is_primary' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'instrument_id' => Instrument::factory(),
        ];
    }
}
