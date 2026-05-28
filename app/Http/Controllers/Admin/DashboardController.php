<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatsService;

class DashboardController extends Controller
{
    public function __construct(private readonly StatsService $statsService)
    {
    }

    public function index()
    {
        return view('admin.dashboard', [
            'overview' => $this->statsService->overview(),
            'timeline' => $this->statsService->timeline(),
            'summary' => $this->statsService->summary(),
        ]);
    }
}
