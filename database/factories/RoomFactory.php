<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'name' => 'Room ' . self::$sequence,
            'capacity' => 12,
            'is_active' => true,
        ];
    }

    /** Persisted room available to new and edited sessions. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /** Persisted room retained for historical/filter-only resolution. */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /** Give a fixture a stable persisted name without creating a fake option. */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
