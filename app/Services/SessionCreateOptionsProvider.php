<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Instrument;
use App\Models\Student;
use App\Models\Teacher;

/**
 * Prepares the complete query-free data contract for Session Create.
 *
 * This is the only owner boundary used by the create controller for student,
 * teacher, instrument, subscription and room option preparation.
 */
final class SessionCreateOptionsProvider
{
    public function __construct(private readonly RoomOptionProvider $rooms)
    {
    }

    /** @return array{students: array<int, array<string, mixed>>, teachers: \Illuminate\Database\Eloquent\Collection, instruments: \Illuminate\Database\Eloquent\Collection, teacher_instrument_map: array<string, array<int, array{id: int, name: string}>>, rooms: array<int, \App\DTOs\RoomOptionData>} */
    public function prepare(): array
    {
        $students = Student::query()
            ->with(['subscriptions.teacher', 'subscriptions.instrument'])
            ->orderBy('full_name')
            ->get()
            ->map(static fn (Student $student): array => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'subscriptions' => $student->subscriptions->map(static fn ($subscription): array => [
                    'teacher_id' => $subscription->teacher_id,
                    'teacher_name' => $subscription->teacher?->full_name,
                    'instrument_id' => $subscription->instrument_id,
                    'instrument_name' => $subscription->instrument?->name_fa ?? $subscription->instrument?->name,
                    'sessions_used' => (int) $subscription->sessions_used,
                    'sessions_allocated' => (int) $subscription->sessions_allocated,
                ])->values()->all(),
            ])->values()->all();

        $teachers = Teacher::query()
            ->with(['instruments' => static fn ($query) => $query
                ->where('instruments.is_active', true)
                ->orderBy('instruments.name_fa')
                ->orderBy('instruments.name')])
            ->orderBy('full_name')
            ->get();
        $instruments = Instrument::query()->active()->orderBy('name_fa')->orderBy('name')->get();
        $teacherInstrumentMap = [];

        foreach ($teachers as $teacher) {
            $teacherInstrumentMap[(string) $teacher->id] = $teacher->instruments
                ->map(static fn (Instrument $instrument): array => [
                    'id' => $instrument->id,
                    'name' => $instrument->name_fa ?: $instrument->name,
                ])->values()->all();
        }

        return [
            'students' => $students,
            'teachers' => $teachers,
            'instruments' => $instruments,
            'teacher_instrument_map' => $teacherInstrumentMap,
            'rooms' => $this->rooms->forSessionInput(),
        ];
    }
}
