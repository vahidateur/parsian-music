<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\AttendanceReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request, AttendanceReportService $service): View
    {
        // Date range filter (default: last 30 days).
        $endDate = $request->filled('end_date')
            ? CarbonImmutable::parse($request->end_date)->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        $startDate = $request->filled('start_date')
            ? CarbonImmutable::parse($request->start_date)->startOfDay()
            : $endDate->subDays(30)->startOfDay();

        $range = [$startDate->toDateString(), $endDate->toDateString()];

        ['totals' => $totals, 'rows' => $rows] = $service->generate($range);

        return view('admin.reports.attendance', compact(
            'startDate',
            'endDate',
            'totals',
            'rows'
        ));
    }
}
