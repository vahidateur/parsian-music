<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Persists manually-created sessions using the canonical room contract. */
final class SessionCreateService
{
    public function __construct(
        private readonly ConflictDetectionService $conflictDetector,
        private readonly RoomResolver $roomResolver,
    ) {}

    /** @param array<string, mixed> $validated */
    public function create(array $validated): ClassSession
    {
        $room = $this->roomResolver->normalize($validated['room'] ?? null);
        if (! $this->roomResolver->fitsLegacyCapacity($room)) {
            throw ValidationException::withMessages(['room' => __('admin.room_name_too_long')]);
        }
        if ($room === null) {
            throw ValidationException::withMessages(['room' => __('admin.room_not_available')]);
        }

        $validated['room'] = $room;

        return DB::transaction(function () use ($validated, $room): ClassSession {
            Student::query()->whereKey($validated['student_id'])->lockForUpdate()->firstOrFail();
            Teacher::query()->whereKey($validated['teacher_id'])->lockForUpdate()->firstOrFail();
            $lockedRoom = Room::query()->where('name', $room)->lockForUpdate()->first();

            if ($lockedRoom === null || ! $lockedRoom->is_active) {
                throw ValidationException::withMessages(['room' => __('admin.room_not_available')]);
            }

            $hasConflict = $this->conflictDetector->checkTeacherConflict(
                (int) $validated['teacher_id'], $validated['session_date'], $validated['start_time'], (int) $validated['duration_minutes']
            ) || $this->conflictDetector->checkRoomConflict(
                $room, $validated['session_date'], $validated['start_time'], (int) $validated['duration_minutes']
            ) || $this->conflictDetector->checkStudentOverlap(
                (int) $validated['student_id'], $validated['session_date'], $validated['start_time'], (int) $validated['duration_minutes']
            );

            if ($hasConflict) {
                throw ValidationException::withMessages(['start_time' => __('admin.session_conflict_error')]);
            }

            $session = ClassSession::create($validated);
            $subscription = Subscription::query()->where([
                'student_id' => $validated['student_id'],
                'teacher_id' => $validated['teacher_id'],
                'instrument_id' => $validated['instrument_id'],
            ])->lockForUpdate()->first();

            if ($subscription) {
                $subscription->increment('sessions_used');
            }

            return $session;
        });
    }
}
