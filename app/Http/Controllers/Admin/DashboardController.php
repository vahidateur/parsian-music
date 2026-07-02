<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service): View
    {
        $today = CarbonImmutable::today();

        $summary = $service->getSummary($today);

        return view('admin.dashboard', [
            'totalStudents'     => $summary['totalStudents'],
            'activeTeachers'    => $summary['activeTeachers'],
            'todaySessions'     => $summary['todaySessions'],
            'recentSessions'    => $summary['recentSessions'],
            'cancelledSessions' => $summary['cancelledSessions'],
            'missedSessions'    => $summary['missedSessions'],
            'recentStudents'    => $summary['recentStudents'],
            'enrollmentTrend'   => $summary['enrollmentTrend'],
            'attendanceStats'   => $summary['attendanceStats'],
        ]);
    }
}
