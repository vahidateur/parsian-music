<?php

namespace Database\Factories;

use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'email' => fake()->safeEmail(),
            'age' => fake()->numberBetween(10, 60),
            'source' => fake()->randomElement(LeadSourceEnum::cases())->value,
            'status' => LeadStatusEnum::New->value,
            'priority' => fake()->randomElement(LeadPriorityEnum::cases())->value,
            'assigned_to' => null,
            'preferred_instrument_id' => null,
            'preferred_teacher_id' => null,
            'next_follow_up_at' => Carbon::now()->addDays(fake()->numberBetween(1, 30)),
            'notes' => fake()->optional()->sentence(),
            'converted_at' => null,
            'converted_student_id' => null,
        ];
    }

    /**
     * Mark lead as overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_follow_up_at' => Carbon::now()->subDays(fake()->numberBetween(1, 10)),
            'status' => fake()->randomElement([
                LeadStatusEnum::New->value,
                LeadStatusEnum::Contacted->value,
                LeadStatusEnum::Interested->value,
                LeadStatusEnum::TrialScheduled->value,
            ]),
        ]);
    }

    /**
     * Status state: `new`.
     *
     * Named `statusNew` because `Factory::new()` is a static framework method.
     */
    public function statusNew(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::New->value,
        ]);
    }

    /** Status state: `contacted`. */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::Contacted->value,
        ]);
    }

    /** Status state: `interested`. */
    public function interested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::Interested->value,
        ]);
    }

    /** Status state: `trial_scheduled`. */
    public function trialScheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::TrialScheduled->value,
        ]);
    }

    /** Status state: `registered`. */
    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::Registered->value,
        ]);
    }

    /** Status state: `lost`. */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::Lost->value,
        ]);
    }

    /**
     * Mark lead as converted.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatusEnum::Registered->value,
            'converted_at' => Carbon::now(),
            'converted_student_id' => fake()->randomNumber(5),
        ]);
    }
}
