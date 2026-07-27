<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class CalendarController extends Controller
{
    public function index(): View
    {
        $teachers = Teacher::query()->orderBy('full_name')->get();
        $students = Student::query()->orderBy('full_name')->get();
        $instruments = Instrument::query()->orderBy('name_fa')->orderBy('name')->get();
        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        return view('admin.calendar.index', compact(
            'teachers',
            'students',
            'instruments',
            'rooms',
        ));
    }

    public function events(CalendarEventRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();

            $query = ClassSession::query()
                ->forDateRange($filters['start'], $filters['end'])
                ->withEnrollmentDetails();

            if (! empty($filters['teacher_id'])) {
                $query->forTeacher((int) $filters['teacher_id']);
            }

            if (! empty($filters['student_id'])) {
                $query->forStudent((int) $filters['student_id']);
            }

            if (! empty($filters['instrument_id'])) {
                $query->forInstrument((int) $filters['instrument_id']);
            }

            if (! empty($filters['room'])) {
                $query->where('room', $filters['room']);
            }

            return CalendarEventResource::collection(
                $query->orderBySchedule()->get()
            )->response();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('admin.calendar_errors.loading_calendar'),
            ], 500);
        }
    }
}
