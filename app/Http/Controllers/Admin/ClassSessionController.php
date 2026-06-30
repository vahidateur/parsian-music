<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\RecurringSchedule;
use App\Services\SessionGeneratorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassSessionController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClassSession::withEnrollmentDetails();

        if ($request->filled('student_id')) {
            $query->forStudent($request->student_id);
        }

        if ($request->filled('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }

        if ($request->filled('instrument_id')) {
            $query->forInstrument($request->instrument_id);
        }

        if ($request->filled('room')) {
            $query->where('room', $request->room);
        }

        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, SessionStatusEnum::values(), true),
                422,
                'Invalid session status filter.'
            );
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('session_date', $request->date);
        }

        $sessions = $query->orderBySchedule()
            ->paginate(20)
            ->withQueryString();

        return view('admin.sessions.index', compact('sessions'));
    }

    public function generate(SessionGeneratorService $generator): RedirectResponse
    {
        $schedules = RecurringSchedule::active()->get();

        $totalCreated = 0;

        foreach ($schedules as $schedule) {
            $created = $generator->generateForSchedule($schedule, 8);
            $totalCreated += $created->count();
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', "{$totalCreated} session(s) generated successfully.");
    }

    public function calendar(Request $request): View
    {
        // Week navigation: default to the current week (Mon-Sun)
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Build the 7-day column range
        $days = CarbonPeriod::create($weekStart, $weekEnd)->toArray();

        // Time slots: 08:00 → 20:00 (inclusive, hourly)
        $hours = range(8, 20);

        // Base query with relations + week range (centralized scopes)
        $query = ClassSession::withEnrollmentDetails()
            ->forDateRange($weekStart->toDateString(), $weekEnd->toDateString());

        if ($request->filled('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }

        if ($request->filled('student_id')) {
            $query->forStudent($request->student_id);
        }

        if ($request->filled('room')) {
            $query->where('room', $request->room);
        }

        $sessions = $query->orderBy('start_time')->get();

        // Group sessions by [date][hour] for O(1) grid lookup
        $grid = [];
        foreach ($sessions as $session) {
            $dateKey = $session->session_date->toDateString();
            $hourKey = (int) $session->start_time->format('G');
            $grid[$dateKey][$hourKey][] = $session;
        }

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

        return view('admin.calendar', compact(
            'weekStart',
            'weekEnd',
            'days',
            'hours',
            'grid',
            'prevWeek',
            'nextWeek'
        ));
    }
}
