<?php

namespace App\Services;

use App\Models\DailyHealthReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CorrelationAnalysisService
{
    public function analysis(string $period = 'month'): array
    {
        $end = Carbon::today();
        $start = $period === 'week' ? $end->copy()->subDays(6) : $end->copy()->subDays(29);
        $cacheKey = 'analysis:correlation:'.$period.':'.$start->toDateString().':'.$end->toDateString();

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($period, $start, $end) {
            $reports = $this->reports($start, $end);
            $comparisons = $this->comparisons($reports);
            $nextDay = $this->nextDayComparisons($reports);
            $ranking = collect([...$comparisons, ...$nextDay])
                ->sortByDesc(fn (array $item) => abs($item['delta'] ?? 0))
                ->values();

            return [
                'period' => $period,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $reports->count(),
                'summary' => $this->summary($ranking),
                'ranking' => $ranking,
                'comparisons' => $comparisons,
                'next_day' => $nextDay,
                'charts' => $this->charts($reports),
                'suggestions' => $this->suggestions($ranking),
            ];
        });
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

    private function comparisons(Collection $reports): array
    {
        return array_values(array_filter([
            $this->compare(
                '睡眠充足对健康评分的影响',
                $reports->filter(fn (DailyHealthReport $report) => $report->sleep_minutes >= 420 && $report->sleep_minutes <= 540),
                $reports->filter(fn (DailyHealthReport $report) => $report->sleep_minutes < 360),
                'health_score',
                '睡眠7-9小时',
                '睡眠少于6小时',
                '分'
            ),
            $this->compare(
                '运动对健康评分的影响',
                $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes >= 30),
                $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes < 30),
                'health_score',
                '运动30分钟以上',
                '运动少于30分钟',
                '分'
            ),
            $this->compare(
                '游戏时间对健康评分的影响',
                $reports->filter(fn (DailyHealthReport $report) => $report->game_minutes <= 90),
                $reports->filter(fn (DailyHealthReport $report) => $report->game_minutes > 120),
                'health_score',
                '游戏90分钟以内',
                '游戏超过120分钟',
                '分'
            ),
            $this->compare(
                '通勤压力对健康评分的影响',
                $reports->filter(fn (DailyHealthReport $report) => $report->commute_minutes <= 60),
                $reports->filter(fn (DailyHealthReport $report) => $report->commute_minutes > 90),
                'health_score',
                '通勤60分钟以内',
                '通勤超过90分钟',
                '分'
            ),
            $this->compare(
                '运动对个人状态的影响',
                $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes >= 30),
                $reports->filter(fn (DailyHealthReport $report) => $report->exercise_minutes < 30),
                'mood_score',
                '运动30分钟以上',
                '运动少于30分钟',
                '分'
            ),
        ]));
    }

    private function nextDayComparisons(Collection $reports): array
    {
        $byDate = $reports->keyBy(fn (DailyHealthReport $report) => $report->report_date->toDateString());
        $rows = collect();

        foreach ($reports as $report) {
            $next = $byDate->get($report->report_date->copy()->addDay()->toDateString());
            if (! $next) {
                continue;
            }

            $rows->push([
                'current' => $report,
                'next_mood_score' => $next->mood_score,
                'next_health_score' => $next->health_score,
                'next_wake_hour' => $this->timeToHour((string) $next->wake_time),
            ]);
        }

        return array_values(array_filter([
            $this->compareArrays(
                '睡眠不足对次日状态的影响',
                $rows->filter(fn (array $row) => $row['current']->sleep_minutes >= 420),
                $rows->filter(fn (array $row) => $row['current']->sleep_minutes < 360),
                'next_mood_score',
                '前一晚睡眠7小时以上',
                '前一晚睡眠少于6小时',
                '分'
            ),
            $this->compareArrays(
                '游戏过长对次日状态的影响',
                $rows->filter(fn (array $row) => $row['current']->game_minutes <= 90),
                $rows->filter(fn (array $row) => $row['current']->game_minutes > 120),
                'next_mood_score',
                '前一晚游戏90分钟以内',
                '前一晚游戏超过120分钟',
                '分'
            ),
            $this->compareArrays(
                '游戏过长对次日起床的影响',
                $rows->filter(fn (array $row) => $row['current']->game_minutes <= 90),
                $rows->filter(fn (array $row) => $row['current']->game_minutes > 120),
                'next_wake_hour',
                '前一晚游戏90分钟以内',
                '前一晚游戏超过120分钟',
                '小时'
            ),
        ]));
    }

    private function compare(string $title, Collection $good, Collection $bad, string $field, string $goodLabel, string $badLabel, string $unit): ?array
    {
        return $this->makeComparison(
            $title,
            $this->avg($good, $field),
            $this->avg($bad, $field),
            $good->whereNotNull($field)->count(),
            $bad->whereNotNull($field)->count(),
            $goodLabel,
            $badLabel,
            $unit
        );
    }

    private function compareArrays(string $title, Collection $good, Collection $bad, string $field, string $goodLabel, string $badLabel, string $unit): ?array
    {
        return $this->makeComparison(
            $title,
            $this->avgArray($good, $field),
            $this->avgArray($bad, $field),
            $good->filter(fn (array $row) => $row[$field] !== null)->count(),
            $bad->filter(fn (array $row) => $row[$field] !== null)->count(),
            $goodLabel,
            $badLabel,
            $unit
        );
    }

    private function makeComparison(string $title, ?float $goodAvg, ?float $badAvg, int $goodCount, int $badCount, string $goodLabel, string $badLabel, string $unit): ?array
    {
        if ($goodAvg === null || $badAvg === null || $goodCount === 0 || $badCount === 0) {
            return null;
        }

        $delta = round($goodAvg - $badAvg, 1);

        return [
            'title' => $title,
            'good_label' => $goodLabel,
            'bad_label' => $badLabel,
            'good_avg' => round($goodAvg, 1),
            'bad_avg' => round($badAvg, 1),
            'delta' => $delta,
            'unit' => $unit,
            'good_count' => $goodCount,
            'bad_count' => $badCount,
            'text' => "{$goodLabel}时平均{$this->metricName($title)}为".round($goodAvg, 1)."{$unit}，{$badLabel}时为".round($badAvg, 1)."{$unit}，差值".abs($delta)."{$unit}。",
        ];
    }

    private function charts(Collection $reports): array
    {
        return [
            [
                'id' => 'sleepMoodChart',
                'title' => '睡眠时长 vs 状态评分',
                'x_label' => '睡眠小时',
                'y_label' => '状态评分',
                'color' => '#1e9fff',
                'points' => $reports
                    ->filter(fn (DailyHealthReport $report) => $report->mood_score !== null)
                    ->map(fn (DailyHealthReport $report) => ['x' => round($report->sleep_minutes / 60, 1), 'y' => $report->mood_score])
                    ->values(),
            ],
            [
                'id' => 'exerciseHealthChart',
                'title' => '运动时长 vs 健康评分',
                'x_label' => '运动分钟',
                'y_label' => '健康评分',
                'color' => '#ff5722',
                'points' => $reports->map(fn (DailyHealthReport $report) => ['x' => $report->exercise_minutes, 'y' => $report->health_score])->values(),
            ],
            [
                'id' => 'gameSleepChart',
                'title' => '游戏时长 vs 睡觉时间',
                'x_label' => '游戏分钟',
                'y_label' => '睡觉时间',
                'color' => '#a233c6',
                'points' => $reports->map(fn (DailyHealthReport $report) => ['x' => $report->game_minutes, 'y' => $this->sleepHour((string) $report->sleep_time)])->values(),
            ],
            [
                'id' => 'commuteMoodChart',
                'title' => '通勤时长 vs 状态评分',
                'x_label' => '通勤分钟',
                'y_label' => '状态评分',
                'color' => '#ffb800',
                'points' => $reports
                    ->filter(fn (DailyHealthReport $report) => $report->mood_score !== null)
                    ->map(fn (DailyHealthReport $report) => ['x' => $report->commute_minutes, 'y' => $report->mood_score])
                    ->values(),
            ],
        ];
    }

    private function suggestions(Collection $ranking): array
    {
        $suggestions = [];

        foreach ($ranking->take(3) as $item) {
            if (str_contains($item['title'], '运动') && $item['delta'] > 0) {
                $suggestions[] = '运动对当前数据呈正向影响，建议优先保证每天至少30分钟运动。';
            }
            if (str_contains($item['title'], '睡眠') && $item['delta'] > 0) {
                $suggestions[] = '睡眠充足明显更有利，建议把睡眠目标稳定在7到9小时。';
            }
            if (str_contains($item['title'], '游戏') && $item['delta'] > 0) {
                $suggestions[] = '游戏时间较短时数据表现更好，建议给游戏设置固定结束时间。';
            }
            if (str_contains($item['title'], '通勤') && $item['delta'] > 0) {
                $suggestions[] = '短通勤日期表现更好，长通勤时建议记录天气、路线和拥堵原因。';
            }
        }

        return array_values(array_unique($suggestions ?: ['当前样本还不够多，建议继续记录至少30天，关联分析会更稳定。']));
    }

    private function summary(Collection $ranking): string
    {
        if ($ranking->isEmpty()) {
            return '当前有效样本还不够，继续记录睡眠、运动、游戏、通勤和状态后，可以生成更准确的影响分析。';
        }

        $top = $ranking->first();

        return "当前最明显的影响因素是：{$top['title']}。{$top['text']}这些结果是基于现有记录的统计推断，样本越多越可靠。";
    }

    private function avg(Collection $reports, string $field): ?float
    {
        $values = $reports->pluck($field)->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : (float) $values->avg();
    }

    private function avgArray(Collection $rows, string $field): ?float
    {
        $values = $rows->pluck($field)->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : (float) $values->avg();
    }

    private function timeToHour(string $time): ?float
    {
        if (! preg_match('/^(\d{2}):(\d{2})/', $time, $matches)) {
            return null;
        }

        return round(((int) $matches[1]) + ((int) $matches[2]) / 60, 2);
    }

    private function sleepHour(string $time): ?float
    {
        $hour = $this->timeToHour($time);
        if ($hour === null) {
            return null;
        }

        return $hour < 12 ? $hour + 24 : $hour;
    }

    private function metricName(string $title): string
    {
        if (str_contains($title, '状态')) {
            return '状态评分';
        }
        if (str_contains($title, '起床')) {
            return '起床时间';
        }

        return '健康评分';
    }
}
