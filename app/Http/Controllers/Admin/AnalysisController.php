<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HealthAnalysisService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function __construct(private readonly HealthAnalysisService $healthAnalysisService)
    {
    }

    public function index(Request $request)
    {
        $period = $request->query('period', 'month');

        return view('admin.analysis.index', [
            'overview' => $this->healthAnalysisService->overview(),
            'chartData' => $this->healthAnalysisService->chartData($period),
            'period' => $period,
        ]);
    }
}
