<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Enums\AttendanceStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassAttendanceController extends Controller
{
    public function show(ClassSession $session): View
    {
        $this->authorize('view', $session);

        $session->load([
            'enrollment.student',
            'enrollment.teacher',
            'enrollment.instrument',
            'attendances',
        ]);

        // The session is tied to a single enrollment → single student.
        // For multi-student sessions (future), this would expand to a roster.
        $students = collect();
        if ($session->enrollment && $session->enrollment->student) {
            $students->push($session->enrollment->student);
        }

        // Key existing attendance by student_id for O(1) lookup
        $attendanceMap = $session->attendances->keyBy('student_id');

        // Mark each student with their current status (if any)
        $students->each(function ($student) use ($attendanceMap) {
            $student->attendance_status = $attendanceMap->get($student->id)?->status;
        });

        $summary = $this->buildSummary($students);
        $completion = $students->isNotEmpty()
            ? (int) round(($summary['marked'] / $students->count()) * 100)
            : 0;

        return view('admin.attendance.show', compact(
            'session',
            'students',
            'summary',
            'completion'
        ));
    }

    public function store(Request $request, ClassSession $session): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'status' => ['required', 'string', 'in:' . implode(',', AttendanceStatusEnum::values())],
            'note' => ['nullable', 'string'],
        ]);

        // Race-condition safe upsert inside a transaction.
        DB::transaction(function () use ($session, $validated, $request) {
            ClassAttendance::updateOrCreate(
                [
                    'class_session_id' => $session->id,
                    'student_id' => $validated['student_id'],
                ],
                [
                    'status' => $validated['status'],
                    'note' => $validated['note'] ?? null,
                    'marked_by' => $request->user()->id,
                    'marked_at' => CarbonImmutable::now(),
                ]
            );
        });

        return redirect()
            ->route('admin.sessions.attendance.show', $session)
            ->with('success', 'Attendance updated.');
    }

    /**
     * Build the summary counts for the chart + progress bar.
     *
     * @param  Collection<int, Student>  $students
     * @return array{present: int, absent: int, late: int, excused: int, marked: int}
     */
    protected function buildSummary(Collection $students): array
    {
        $base = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'marked' => 0,
        ];

        foreach ($students as $student) {
            $status = $student->attendance_status;
            if ($status === null) {
                continue;
            }
            $base[$status] = ($base[$status] ?? 0) + 1;
            $base['marked']++;
        }

        return $base;
    }
}
