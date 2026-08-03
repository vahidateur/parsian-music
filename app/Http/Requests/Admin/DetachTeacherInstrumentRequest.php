<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

/**
 * Validation contract for detaching an instrument from a teacher.
 *
 * Requirements: 6.5, 6.7
 */
class DetachTeacherInstrumentRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'exists:instruments,id'],
        ];
    }
}
