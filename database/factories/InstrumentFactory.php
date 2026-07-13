<?php

namespace Database\Factories;

use App\Models\Instrument;
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
}
