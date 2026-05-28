<?php

use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('summaries:generate {date?} {--days=1}', function () {
    $statsService = app(StatsService::class);
    $base = Carbon::parse($this->argument('date') ?: today());
    $days = max((int) $this->option('days'), 1);

    for ($i = 0; $i < $days; $i++) {
        $target = $base->copy()->subDays($i)->toDateString();
        $summary = $statsService->persistDailySummary($target);
        $this->info($target.'：'.$summary->summary_text);
    }
})->purpose('Generate and persist daily habit summaries');
