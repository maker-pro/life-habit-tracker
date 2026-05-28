<?php

namespace App\Services;

use App\Models\DailyHealthReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ComprehensiveReportService
{
    public function report(string $period = 'week', ?string $date = null): array
    {
        $base = Carbon::parse($date ?: today());
        [$start, $end, $label] = $this->periodRange($period, $base);
        $cacheKey = 'report:comprehensive:'.$period.':'.$start->toDateString().':'.$end->toDateString();

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($start, $end, $label, $period) {
            $reports = $this->reports($start, $end);
            $metrics = $this->metrics($reports);

            return [
                'period' => $period,
                'label' => $label,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'metrics' => $metrics,
                'summary' => $this->summaryText($label, $metrics),
                'insights' => $this->insights($reports, $metrics),
                'suggestions' => $this->suggestions($metrics),
                'chart' => $this->chart($reports),
                'rows' => $reports->map(fn (DailyHealthReport $report) => [
                    'date' => $report->report_date->toDateString(),
                    'sleep_hours' => round($report->sleep_minutes / 60, 1),
                    'commute_minutes' => $report->commute_minutes,
                    'study_minutes' => $report->study_minutes,
                    'exercise_minutes' => $report->exercise_minutes,
                    'game_minutes' => $report->game_minutes,
                    'weight' => $report->weight,
                    'mood_level' => $report->mood_level,
                    'mood_score' => $report->mood_score,
                    'health_score' => $report->health_score,
                    'analysis_text' => $report->analysis_text,
                ])->values(),
            ];
        });
    }

    private function periodRange(string $period, Carbon $base): array
    {
        return match ($period) {
            'day' => [$base->copy(), $base->copy(), '今日'],
            'month' => [$base->copy()->subDays(29), $base->copy(), '最近30天'],
            default => [$base->copy()->subDays(6), $base->copy(), '最近7天'],
        };
    }

    private function reports(Carbon $start, Carbon $end): Collection
    {
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            app(HealthAnalysisService::class)->persistDailyReport($date->toDateString());
        }

        return DailyHealthReport::whereDate('report_date', '>=', $start->toDateString())
            ->whereDate('report_date', '<=', $end->toDateString())
            ->orderBy('report_date')
            ->get();
    }

    private function metrics(Collection $reports): array
    {
        $awakeMinutes = max((int) $reports->sum('awake_minutes'), 1);
        $moodScores = $reports->pluck('mood_score')->filter();
        $weights = $reports->pluck('weight')->filter();

        return [
            'days' => $reports->count(),
            'avg_health_score' => round($reports->avg('health_score') ?: 0, 1),
            'avg_sleep_hours' => round(($reports->avg('sleep_minutes') ?: 0) / 60, 1),
            'avg_commute_minutes' => round($reports->avg('commute_minutes') ?: 0, 1),
            'avg_exercise_minutes' => round($reports->avg('exercise_minutes') ?: 0, 1),
            'avg_game_minutes' => round($reports->avg('game_minutes') ?: 0, 1),
            'study_ratio' => round($reports->sum('study_minutes') / $awakeMinutes * 100, 1),
            'exercise_ratio' => round($reports->sum('exercise_minutes') / $awakeMinutes * 100, 1),
            'game_ratio' => round($reports->sum('game_minutes') / $awakeMinutes * 100, 1),
            'avg_mood_score' => $moodScores->isEmpty() ? null : round($moodScores->avg(), 1),
            'avg_weight' => $weights->isEmpty() ? null : round($weights->avg(), 1),
            'long_commute_days' => $reports->where('commute_minutes', '>', 90)->count(),
            'short_sleep_days' => $reports->filter(fn (DailyHealthReport $report) => $report->sleep_minutes < 360)->count(),
            'exercise_days' => $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes >= 30)->count(),
            'heavy_game_days' => $reports->filter(fn (DailyHealthReport $report) => $report->game_minutes > 120)->count(),
        ];
    }

    private function summaryText(string $label, array $metrics): string
    {
        $text = "{$label}综合健康评分平均为{$metrics['avg_health_score']}分，平均睡眠{$metrics['avg_sleep_hours']}小时，平均通勤{$metrics['avg_commute_minutes']}分钟。";
        $text .= "清醒时间中，学习占{$metrics['study_ratio']}%，运动占{$metrics['exercise_ratio']}%，游戏占{$metrics['game_ratio']}%。";

        if ($metrics['avg_mood_score']) {
            $text .= "个人状态平均评分{$metrics['avg_mood_score']}分。";
        }

        return $text;
    }

    private function insights(Collection $reports, array $metrics): array
    {
        $insights = [];

        if ($metrics['short_sleep_days'] > 0) {
            $insights[] = "有{$metrics['short_sleep_days']}天睡眠少于6小时，睡眠不足可能拉低白天状态和恢复能力。";
        }

        if ($metrics['exercise_days'] > 0) {
            $exerciseAvg = $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes >= 30)->avg('health_score');
            $restAvg = $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes < 30)->avg('health_score');
            if ($restAvg !== null) {
                $diff = round($exerciseAvg - $restAvg, 1);
                $insights[] = "运动30分钟以上的日期，健康评分平均比未达标日期高{$diff}分。";
            }
        }

        if ($metrics['heavy_game_days'] > 0) {
            $insights[] = "有{$metrics['heavy_game_days']}天游戏时间超过120分钟，建议观察这些日期后的睡觉时间和次日状态。";
        }

        if ($metrics['long_commute_days'] > 0) {
            $insights[] = "有{$metrics['long_commute_days']}天通勤超过90分钟，长通勤可能增加疲劳感，可结合备注分析原因。";
        }

        if (empty($insights)) {
            $insights[] = '本周期暂无明显风险信号，继续保持稳定记录，数据越完整，分析会越准确。';
        }

        return $insights;
    }

    private function suggestions(array $metrics): array
    {
        $suggestions = [];

        if ($metrics['avg_sleep_hours'] < 7) {
            $suggestions[] = '平均睡眠低于7小时，建议优先把睡觉时间前移15到30分钟。';
        }

        if ($metrics['avg_exercise_minutes'] < 20) {
            $suggestions[] = '平均运动时间偏少，可以先从每天20到30分钟低门槛运动开始。';
        }

        if ($metrics['avg_game_minutes'] > 90) {
            $suggestions[] = '平均游戏时间偏长，建议设置固定结束时间，避免影响睡眠。';
        }

        if ($metrics['avg_commute_minutes'] > 90) {
            $suggestions[] = '通勤时间偏长时，建议用备注记录天气、堵车、路线变化，方便后续找规律。';
        }

        if (empty($suggestions)) {
            $suggestions[] = '当前作息和时间分配相对稳定，建议继续记录体重和个人状态，后续可以做更准确的关联分析。';
        }

        return $suggestions;
    }

    private function chart(Collection $reports): array
    {
        return [
            'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
            'health' => $reports->pluck('health_score')->values(),
            'sleep' => $reports->pluck('sleep_minutes')->map(fn ($value) => round($value / 60, 1))->values(),
            'commute' => $reports->pluck('commute_minutes')->values(),
            'exercise' => $reports->pluck('exercise_minutes')->values(),
            'game' => $reports->pluck('game_minutes')->values(),
            'mood' => $reports->pluck('mood_score')->values(),
        ];
    }
}
