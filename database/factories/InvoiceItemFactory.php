<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Database\Factories\Concerns\HasParentStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    use HasParentStates;

    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            ...$this->parentAttributes(),
            'title' => 'Tuition fee',
            'description' => null,
            'quantity' => 1,
            'unit_price' => 100000,
            'discount' => 0,
            'total' => 100000,
            'sort_order' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parentAttributes(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
        ];
    }
}
