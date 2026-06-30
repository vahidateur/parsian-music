<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Reports\TeacherPanelService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherPanelController extends Controller
{
    public function index(Request $request, Teacher $teacher, TeacherPanelService $service): View
    {
        // Use CarbonImmutable for stable, non-mutating date calculations.
        $weekStart = CarbonImmutable::today()->startOfWeek();
        $weekEnd = $weekStart->endOfWeek();

        $summary = $service->getPanelData($teacher, $weekStart, $weekEnd);

        return view('admin.teachers.panel', array_merge(
            ['teacher' => $teacher],
            $summary
        ));
    }
}
