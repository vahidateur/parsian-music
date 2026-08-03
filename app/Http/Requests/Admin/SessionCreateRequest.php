<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoomResolutionEnum;
use App\Models\ClassSession;
use App\Services\RoomResolver;
use Illuminate\Foundation\Http\FormRequest;

final class SessionCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ClassSession::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'instrument_id' => ['required', 'integer', 'exists:instruments,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:15:00', 'before_or_equal:21:30'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:120'],
            'room' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('room')) {
            $this->merge(['room' => app(RoomResolver::class)->normalize($this->input('room'))]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $room = $this->input('room');
            if (! is_string($room) || $room === '') {
                return;
            }

            if (app(RoomResolver::class)->resolve($room) !== RoomResolutionEnum::ResolvedActive) {
                $validator->errors()->add('room', __('admin.room_not_available'));
            }
        });
    }
}
