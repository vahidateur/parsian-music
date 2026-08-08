<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\InvoiceAction;
use App\Support\PersianTextNormalizer;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the invoice Record_Form (create and edit).
 *
 * `due_date` carries the date-ordering bound against `issue_date`. The numeric
 * upper bounds match the persisted `decimal(12, 2)` money columns and the
 * `unsignedSmallInteger` quantity column, so an out-of-range amount is reported
 * as a field error instead of failing at the database.
 *
 * Requirements: 6.5, 6.7
 */
class InvoiceRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return InvoiceAction::NORMALIZED_FIELDS;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        $this->merge([
            'items' => array_map(
                static fn (mixed $item): mixed => is_array($item)
                    ? PersianTextNormalizer::fields($item, InvoiceAction::ITEM_NORMALIZED_FIELDS)
                    : $item,
                $items
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id'    => ['required', 'exists:students,id'],
            'enrollment_id' => [
                'nullable',
                Rule::exists('student_enrollments', 'id')
                    ->where('student_id', $this->input('student_id')),
            ],
            'issue_date'    => ['required', 'date'],
            'due_date'      => ['required', 'date', 'after_or_equal:issue_date'],
            'tax'           => ['nullable', 'numeric', 'min:0', 'max:' . self::MONEY_MAX],
            'notes'         => ['nullable', 'string', 'max:2000'],

            'items'               => ['required', 'array', 'min:1', 'max:' . self::TINY_INTEGER_SLOTS],
            'items.*.title'       => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity'    => ['required', 'integer', 'min:1', 'max:' . self::SMALL_INTEGER_MAX],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0', 'max:' . self::MONEY_MAX],
            'items.*.discount'    => ['nullable', 'numeric', 'min:0', 'max:' . self::MONEY_MAX],
        ];
    }
}
