<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\TeacherReportService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class TeacherReportController extends Controller
{
    public function index(TeacherReportService $service): View
    {
        // Fixed 30-day window (last 30 days).
        $endDate = CarbonImmutable::today()->endOfDay();
        $startDate = $endDate->subDays(30)->startOfDay();
        $range = [$startDate->toDateString(), $endDate->toDateString()];

        $rows = $service->generate($range);

        return view('admin.reports.teachers', compact('rows', 'startDate', 'endDate'));
    }
}
