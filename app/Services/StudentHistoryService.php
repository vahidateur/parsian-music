<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Helpers\Jalalian;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Derives timeline events for a student from existing tables.
 * No new table required — all events are computed on demand.
 *
 * Event types:
 *   student_created, enrollment_created, teacher_changed,
 *   instrument_changed, session_completed, session_cancelled,
 *   attendance_marked, discount_assigned, admin_note
 */
class StudentHistoryService
{
    private const MAX_EVENTS = 100;

    /**
     * Build a timeline for the given student.
     *
     * @return Collection<int, array{
     *     type: string,
     *     timestamp: Carbon,
     *     description: string,
     *     meta: array
     * }>
     */
    public function buildTimeline(Student $student): Collection
    {
        $events = collect();

        // 1. student_created
        $events->push([
            'type'        => 'student_created',
            'timestamp'   => $student->created_at,
            'description' => __('admin.history_student_created_desc', ['name' => $student->full_name]),
            'meta'        => [],
        ]);

        // 2. admin_note — only when note set AFTER creation
        if ($student->notes
            && $student->updated_at
            && $student->updated_at->ne($student->created_at)
        ) {
            $events->push([
                'type'        => 'admin_note',
                'timestamp'   => $student->updated_at,
                'description' => __('admin.history_note_excerpt'),
                'meta'        => ['excerpt' => mb_substr($student->notes, 0, 100)],
            ]);
        }

        // Load enrollments including soft-deleted, with relations
        $enrollments = $student
            ->enrollments()
            ->withTrashed()
            ->with(['teacher', 'instrument'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $teacherName    = $enrollment->teacher?->full_name  ?? '—';
            $instrumentName = $enrollment->instrument?->display_name ?? '—';

            // 3. enrollment_created
            $events->push([
                'type'        => 'enrollment_created',
                'timestamp'   => $enrollment->created_at,
                'description' => __('admin.history_enrollment_created_desc', [
                    'instrument' => $instrumentName,
                    'teacher'    => $teacherName,
                ]),
                'meta' => [
                    'teacher'    => $teacherName,
                    'instrument' => $instrumentName,
                ],
            ]);

            // 4. teacher_changed / instrument_changed
            // Approximation: if updated_at > created_at, assume a change happened.
            // We can't know *what* changed without audit logs, so we emit both
            // only when updated_at strictly differs from created_at.
            if ($enrollment->updated_at && $enrollment->updated_at->ne($enrollment->created_at)) {
                $events->push([
                    'type'        => 'teacher_changed',
                    'timestamp'   => $enrollment->updated_at,
                    'description' => __('admin.history_teacher_changed_desc', ['teacher' => $teacherName]),
                    'meta'        => ['teacher' => $teacherName],
                ]);
                $events->push([
                    'type'        => 'instrument_changed',
                    'timestamp'   => $enrollment->updated_at,
                    'description' => __('admin.history_instrument_changed_desc', ['instrument' => $instrumentName]),
                    'meta'        => ['instrument' => $instrumentName],
                ]);
            }
        }

        // Collect enrollment IDs (including soft-deleted) for session queries
        $enrollmentIds = $enrollments->pluck('id');

        if ($enrollmentIds->isNotEmpty()) {
            // Build lookup: enrollment_id => instrument display_name
            $instrumentByEnrollment = $enrollments->pluck('instrument.name_fa', 'id')
                ->map(fn ($n, $id) => $n ?: ($enrollments->firstWhere('id', $id)?->instrument?->name ?? '—'));

            // 5+6. session_completed / session_cancelled
            ClassSession::whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('status', [SessionStatusEnum::Completed->value, SessionStatusEnum::Cancelled->value])
                ->get()
                ->each(function (ClassSession $session) use (&$events, $instrumentByEnrollment) {
                    $type           = $session->status->value === SessionStatusEnum::Completed->value
                        ? 'session_completed' : 'session_cancelled';
                    $jalaliDate     = Jalalian::fromCarbon($session->session_date);
                    $instrumentName = $instrumentByEnrollment[$session->enrollment_id] ?? '—';

                    $events->push([
                        'type'        => $type,
                        'timestamp'   => $session->updated_at ?? $session->created_at,
                        'description' => __("admin.history_{$type}_desc", [
                            'date'       => $jalaliDate,
                            'instrument' => $instrumentName,
                        ]),
                        'meta' => [
                            'session_date' => $jalaliDate,
                            'instrument'   => $instrumentName,
                        ],
                    ]);
                });

            // 7. discount_assigned — sessions that have fee/discount set
            ClassSession::whereIn('enrollment_id', $enrollmentIds)
                ->where(function ($q) {
                    $q->whereNotNull('session_fee')
                      ->orWhere('discount', '>', 0);
                })
                ->get()
                ->each(function (ClassSession $session) use (&$events) {
                    $events->push([
                        'type'        => 'discount_assigned',
                        'timestamp'   => $session->updated_at ?? $session->created_at,
                        'description' => __('admin.history_discount_assigned_desc'),
                        'meta'        => [
                            'session_fee' => $session->session_fee,
                            'discount'    => $session->discount,
                        ],
                    ]);
                });
        }

        // 8. attendance_marked (absent or late)
        ClassAttendance::where('student_id', $student->id)
            ->whereIn('status', [AttendanceStatusEnum::Absent->value, AttendanceStatusEnum::Late->value])
            ->with('classSession')
            ->get()
            ->each(function (ClassAttendance $attendance) use (&$events) {
                $statusValue = $attendance->status instanceof \BackedEnum
                    ? $attendance->status->value
                    : (string) $attendance->status;
                $jalaliDate = $attendance->classSession
                    ? Jalalian::fromCarbon($attendance->classSession->session_date)
                    : '—';
                $ts = $attendance->marked_at ?? $attendance->created_at;

                $events->push([
                    'type'        => 'attendance_marked',
                    'timestamp'   => $ts,
                    'description' => __('admin.history_attendance_marked_desc', [
                        'status' => __('admin.' . $statusValue),
                        'date'   => $jalaliDate,
                    ]),
                    'meta' => [
                        'attendance_status' => $statusValue,
                        'session_date'      => $jalaliDate,
                    ],
                ]);
            });

        // Sort descending by timestamp, cap at MAX_EVENTS
        return $events
            ->filter(fn ($e) => $e['timestamp'] !== null)
            ->sortByDesc(fn ($e) => $e['timestamp']->timestamp)
            ->values()
            ->take(self::MAX_EVENTS);
    }
}
