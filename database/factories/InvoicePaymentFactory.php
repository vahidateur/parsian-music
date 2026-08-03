<?php

namespace Database\Factories;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    use HasParentStates;

    protected $model = InvoicePayment::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'amount' => 100000,
            'paid_at' => now(),
            'method' => PaymentMethodEnum::Cash->value,
            'status' => PaymentStatusEnum::Completed->value,
            'reference' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'invoice_id' => Invoice::factory()->state([
                'status' => InvoiceStatusEnum::Issued->value,
            ]),
            'created_by' => User::factory(),
        ];
    }

    /** Status state: `pending`. */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::Pending->value,
        ]);
    }

    /** Status state: `completed`. */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::Completed->value,
        ]);
    }

    /** Status state: `failed`. */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::Failed->value,
        ]);
    }

    /** Status state: `refunded`. */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::Refunded->value,
        ]);
    }
}
