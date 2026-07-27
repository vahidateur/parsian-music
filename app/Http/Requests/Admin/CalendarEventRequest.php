<?php

namespace App\Http\Requests\Admin;

use DateTimeImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start'         => ['required', 'date_format:Y-m-d'],
            'end'           => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:start'],
            'teacher_id'    => ['nullable', 'integer', 'exists:teachers,id'],
            'student_id'    => ['nullable', 'integer', 'exists:students,id'],
            'room'          => ['nullable', 'string', 'max:20'],
            'instrument_id' => ['nullable', 'integer', 'exists:instruments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'start.required'         => 'The start date is required.',
            'start.date_format'      => 'The start date must be in the Y-m-d format.',
            'end.required'           => 'The end date is required.',
            'end.date_format'        => 'The end date must be in the Y-m-d format.',
            'end.after_or_equal'     => 'The end date must be after or equal to the start date.',
            'teacher_id.integer'     => 'The teacher ID must be an integer.',
            'teacher_id.exists'      => 'The selected teacher is invalid.',
            'student_id.integer'     => 'The student ID must be an integer.',
            'student_id.exists'      => 'The selected student is invalid.',
            'room.string'            => 'The room must be a string.',
            'room.max'               => 'The room may not be greater than 20 characters.',
            'instrument_id.integer'  => 'The instrument ID must be an integer.',
            'instrument_id.exists'   => 'The selected instrument is invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start'         => 'start date',
            'end'           => 'end date',
            'teacher_id'    => 'teacher',
            'student_id'    => 'student',
            'room'          => 'room',
            'instrument_id' => 'instrument',
        ];
    }

    /**
     * Add cross-field date constraints after the individual dates have passed
     * their format validation.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('start') || $validator->errors()->has('end')) {
                return;
            }

            $start = $this->input('start');
            $end = $this->input('end');

            if (! is_string($start) || ! is_string($end)) {
                return;
            }

            $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
            $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);

            if ($startDate === false || $endDate === false) {
                return;
            }

            // Keep the API contract explicit even if the rule validator does not
            // compare the two already-formatted date values as expected.
            if ($startDate > $endDate && ! $validator->errors()->has('end')) {
                $validator->errors()->add(
                    'end',
                    'The end date must be after or equal to the start date.'
                );

                return;
            }

            if ($startDate->diff($endDate)->days > 92) {
                $validator->errors()->add(
                    'end',
                    'The selected date range may not exceed 92 days.'
                );
            }
        }];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->routeIs('admin.calendar.events')) {
            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
