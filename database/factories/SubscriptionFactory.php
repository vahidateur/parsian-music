<?php

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    use HasParentStates;

    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'sessions_allocated' => 4,
            'sessions_used' => 0,
            'monthly_fee' => 3000000,
            'payment_status' => 'unpaid',
            'renewal_date' => today()->addMonth()->toDateString(),
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'instrument_id' => Instrument::factory(),
        ];
    }
}
