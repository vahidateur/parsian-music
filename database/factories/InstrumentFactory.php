<?php

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\TeacherInstrument;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstrumentFactory extends Factory
{
    protected $model = Instrument::class;
    protected static $instrumentCounter = 0;

    public function definition(): array
    {
        self::$instrumentCounter++;
        $uniqueSuffix = '-' . self::$instrumentCounter;
        return [
            'name' => $this->faker->unique(reset: true)->word() . $uniqueSuffix,
            'name_fa' => $this->faker->unique(reset: true)->word() . $uniqueSuffix,
            'slug' => $this->faker->unique(reset: true)->slug() . $uniqueSuffix,
            'is_active' => true,
        ];
    }

    /**
     * Instrument assigned to one teacher, which blocks deletion
     * (see InstrumentController::destroy in-use guard).
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (Instrument $instrument): void {
            TeacherInstrument::factory()->create(['instrument_id' => $instrument->id]);
        });
    }

    /** Instrument without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
