<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CorrelationAnalysisService;
use App\Services\HealthAnalysisService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly HealthAnalysisService $healthAnalysisService,
        private readonly CorrelationAnalysisService $correlationAnalysisService
    )
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

    public function topic(Request $request, string $topic)
    {
        abort_unless(in_array($topic, ['sleep', 'commute', 'time', 'body'], true), 404);

        $period = $request->query('period', 'month');

        return view('admin.analysis.topic', [
            'topic' => $this->healthAnalysisService->topic($topic, $period),
            'period' => $period,
        ]);
    }

    public function correlation(Request $request)
    {
        $period = $request->query('period', 'month');

        return view('admin.analysis.correlation', [
            'analysis' => $this->correlationAnalysisService->analysis($period),
            'period' => $period,
        ]);
    }
}
