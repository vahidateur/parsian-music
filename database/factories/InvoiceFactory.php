<?php

namespace Database\Factories;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Student;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    use HasParentStates;

    protected $model = Invoice::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;
        $sequence = str_pad((string) self::$sequence, 12, '0', STR_PAD_LEFT);

        return [
            'uuid' => '00000000-0000-4000-8000-' . $sequence,
            'invoice_number' => 'INV-FACTORY-' . self::$sequence,
            ...$this->parentAttributes(),
            'enrollment_id' => null,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'subtotal' => 100000,
            'discount' => 0,
            'tax' => 0,
            'total' => 100000,
            'currency' => 'IRR',
            'status' => InvoiceStatusEnum::Draft->value,
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
        ];
    }

    /** Status state: `draft`. */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::Draft->value,
        ]);
    }

    /** Status state: `issued`. */
    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::Issued->value,
        ]);
    }

    /** Status state: `partially_paid`. */
    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::PartiallyPaid->value,
        ]);
    }

    /** Status state: `paid`. */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::Paid->value,
        ]);
    }

    /** Status state: `overdue` with a past due date. */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::Overdue->value,
            'due_date' => today()->subDays(7)->toDateString(),
        ]);
    }

    /** Status state: `cancelled`. */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatusEnum::Cancelled->value,
        ]);
    }

    /**
     * Invoice with one completed payment, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            InvoicePayment::factory()->create([
                'invoice_id' => $invoice->id,
                'status' => PaymentStatusEnum::Completed->value,
            ]);
        });
    }

    /** Invoice without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
