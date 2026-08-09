<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\FilterContext;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\RoomResolver;
use App\Services\SelectionContextService;
use Illuminate\Validation\Rule;

final class SessionEditRequest extends AdminFormRequest
{
    /** @var list<string> */
    private const EDITABLE_FIELDS = [
        'student_id',
        'teacher_id',
        'instrument_id',
        'session_date',
        'start_time',
        'duration_minutes',
        'status',
        'room',
        'notes',
    ];

    /** @var list<string> */
    private const PROTECTED_FIELDS = [
        'enrollment_id',
        'session_fee',
        'discount',
        'recurring_schedule_id',
    ];

    /** @var list<string> */
    private const TRANSPORT_FIELDS = [
        '_token',
        '_method',
        'updated_at',
        'return_context',
        'return_page',
    ];

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'instrument_id' => ['required', 'integer', 'exists:instruments,id'],
            'session_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:15:00', 'before_or_equal:21:30'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:120'],
            'status' => ['required', Rule::enum(SessionStatusEnum::class)],
            'room' => ['nullable', 'string', 'max:' . RoomResolver::LEGACY_ROOM_NAME_MAX_LENGTH],
            'notes' => ['nullable', 'string'],
            'updated_at' => ['nullable', 'string', 'max:80'],
            'return_context' => ['nullable'],
            'return_page' => ['nullable', 'integer', 'min:1'],
            ...array_fill_keys(self::PROTECTED_FIELDS, ['prohibited']),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('room')) {
            $this->merge(['room' => app(RoomResolver::class)->normalize($this->input('room'))]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->rejectUnknownFields($validator);
            $this->rejectProtectedFields($validator);
            $this->validateRoom($validator);
            $this->validateReturnContext($validator);
        });
    }

    /** @return array<string, mixed> */
    public function validatedRelationModels(): array
    {
        return [
            'student' => Student::query()->find($this->integer('student_id')),
            'teacher' => Teacher::query()->find($this->integer('teacher_id')),
            'instrument' => Instrument::query()->find($this->integer('instrument_id')),
        ];
    }

    public function returnContext(): ?FilterContext
    {
        $context = $this->validated('return_context');

        if ($context === null || $context === '') {
            return null;
        }

        return app(SelectionContextService::class)->verify($context, 'sessions');
    }

    /** @return array<string, string|int|bool> */
    public function returnQueryParameters(): array
    {
        $context = $this->returnContext();
        $query = $context === null
            ? []
            : app(SelectionContextService::class)->queryInput($context);

        if ($this->filled('return_page')) {
            $query['page'] = $this->integer('return_page');
        }

        return $query;
    }

    private function rejectUnknownFields($validator): void
    {
        $allowed = array_merge(self::EDITABLE_FIELDS, self::TRANSPORT_FIELDS, self::PROTECTED_FIELDS);

        foreach (array_keys($this->all()) as $field) {
            if (! in_array($field, $allowed, true)) {
                $validator->errors()->add($field, __('admin.session_edit_unexpected_field'));
            }
        }
    }

    private function rejectProtectedFields($validator): void
    {
        foreach (self::PROTECTED_FIELDS as $field) {
            if (array_key_exists($field, $this->all())) {
                $validator->errors()->add($field, __('admin.session_edit_protected_field'));
            }
        }
    }

    private function validateRoom($validator): void
    {
        $room = $this->input('room');
        if ($room === null || $room === '') {
            return;
        }

        $resolver = app(RoomResolver::class);
        if (! $resolver->fitsLegacyCapacity($room)) {
            $validator->errors()->add('room', __('admin.room_name_too_long'));
            return;
        }

        $session = $this->route('session');
        $currentRoom = $session instanceof ClassSession ? $session->getRawOriginal('room') : null;
        if ($currentRoom !== null && $room === $currentRoom) {
            return;
        }

        if ($resolver->active($room) === null) {
            $validator->errors()->add('room', __('admin.room_not_available'));
        }
    }

    private function validateReturnContext($validator): void
    {
        $context = $this->input('return_context');
        if ($context === null || $context === '') {
            return;
        }

        if (! is_array($context) && ! is_string($context)) {
            $validator->errors()->add('return_context', __('admin.session_edit_invalid_return_context'));
            return;
        }

        try {
            app(SelectionContextService::class)->verify($context, 'sessions');
        } catch (\Throwable) {
            $validator->errors()->add('return_context', __('admin.session_edit_invalid_return_context'));
        }
    }
}
