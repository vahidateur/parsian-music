<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\InstrumentAction;
use App\Models\Instrument;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the instrument Record_Form (create and edit).
 *
 * Requirements: 6.5, 6.7
 */
class InstrumentRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return InstrumentAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $instrument = $this->route('instrument');
        $ignoredId = $instrument instanceof Instrument ? $instrument->id : null;

        $nameFa = [
            'required',
            'string',
            'max:100',
            Rule::unique('instruments', 'name_fa')->ignore($ignoredId),
        ];

        // InstrumentAction::englishName() falls back to name_fa when name is blank,
        // so the fallback value is what reaches the unique `name` column and it is
        // checked against that column under the field the user actually submitted.
        if ($this->fallsBackToPersianName()) {
            $nameFa[] = Rule::unique('instruments', 'name')->ignore($ignoredId);
        }

        return [
            'name_fa' => $nameFa,
            'name' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('instruments', 'name')->ignore($ignoredId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * True when the submitted English name is empty, which makes the Persian name
     * the value persisted into the unique `name` column.
     */
    private function fallsBackToPersianName(): bool
    {
        $name = $this->input('name');

        return ! is_string($name) || trim($name) === '';
    }
}
