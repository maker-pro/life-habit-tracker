<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ComprehensiveReportService;
use App\Services\CorrelationAnalysisService;
use App\Services\StatsService;
use App\Services\HealthAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
        private readonly HealthAnalysisService $healthAnalysisService,
        private readonly ComprehensiveReportService $comprehensiveReportService,
        private readonly CorrelationAnalysisService $correlationAnalysisService
    )
    {
    }

    public function overview(): JsonResponse
    {
        return $this->success($this->statsService->overview());
    }

    public function daily(Request $request): JsonResponse
    {
        return $this->success($this->statsService->daily($request->query('date')));
    }

    public function weekly(Request $request): JsonResponse
    {
        return $this->success($this->statsService->weekly($request->query('week')));
    }

    public function monthly(Request $request): JsonResponse
    {
        return $this->success($this->statsService->monthly($request->query('month')));
    }

    public function habit(int $id): JsonResponse
    {
        return $this->success($this->statsService->habit($id));
    }

    public function timeline(Request $request): JsonResponse
    {
        return $this->success($this->statsService->timeline($request->query('date')));
    }

    public function summary(): JsonResponse
    {
        return $this->success($this->statsService->summary());
    }

    public function health(): JsonResponse
    {
        return $this->success($this->healthAnalysisService->overview());
    }

    public function comprehensive(Request $request): JsonResponse
    {
        return $this->success($this->comprehensiveReportService->report($request->query('period', 'week')));
    }

    public function correlation(Request $request): JsonResponse
    {
        return $this->success($this->correlationAnalysisService->analysis($request->query('period', 'month')));
    }

    private function success(mixed $data = [], string $message = 'success'): JsonResponse
    {
        return response()->json(['code' => 200, 'message' => $message, 'data' => $data]);
    }
}
