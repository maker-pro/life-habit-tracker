<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly StatsService $statsService)
    {
    }

    public function summary()
    {
        return view('admin.reports.summary', [
            'overview' => $this->statsService->overview(),
            'summary' => $this->statsService->summary(),
            'weekly' => $this->statsService->weekly(),
            'monthly' => $this->statsService->monthly(),
        ]);
    }

    public function timeline(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        return view('admin.reports.timeline', [
            'date' => $date,
            'timeline' => $this->statsService->timeline($date),
        ]);
    }

    public function calendar(Request $request)
    {
        $month = $request->query('month', Carbon::today()->format('Y-m'));
        $base = Carbon::parse($month.'-01');
        $days = [];

        for ($date = $base->copy()->startOfMonth(); $date->lte($base->copy()->endOfMonth()); $date->addDay()) {
            $days[] = $this->statsService->daily($date->toDateString());
        }

        return view('admin.reports.calendar', [
            'month' => $month,
            'days' => $days,
        ]);
    }
}
