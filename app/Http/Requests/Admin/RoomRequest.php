<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\RoomAction;
use App\Models\Room;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the room Record_Form (create and edit).
 *
 * The capacity bound matches the persisted integer column, so an out-of-range
 * value is rejected as a field error instead of failing at the database.
 *
 * Requirements: 6.5, 6.7
 */
class RoomRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return RoomAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $room = $this->route('room');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')->ignore($room instanceof Room ? $room->id : null),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:' . self::INTEGER_MAX],
        ];
    }
}
