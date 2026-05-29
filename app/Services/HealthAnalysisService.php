<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\DailyHealthReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HealthAnalysisService
{
    public function overview(?string $date = null): array
    {
        $day = Carbon::parse($date ?: today())->toDateString();

        return Cache::remember('health:overview:'.$day, now()->addMinutes(5), function () use ($day) {
            return [
                'today' => $this->persistDailyReport($day),
                'weekly' => $this->rangeReport(Carbon::parse($day)->subDays(6), Carbon::parse($day), '最近7天'),
                'monthly' => $this->rangeReport(Carbon::parse($day)->subDays(29), Carbon::parse($day), '最近30天'),
            ];
        });
    }

    public function persistDailyReport(?string $date = null): ?DailyHealthReport
    {
        if (! $date) {
            return null;
        }

        $day = Carbon::parse($date)->toDateString();
        $checkins = Checkin::with('habit')->whereDate('checkin_date', $day)->get();

        $wakeTime = $this->firstTime($checkins, ['wake'], '07:30');
        $sleepTime = $this->firstTime($checkins, ['sleep'], '23:00');
        $awakeMinutes = $this->minutesBetween($wakeTime, $sleepTime);
        $previousSleep = $this->firstTime(
            Checkin::with('habit')->whereDate('checkin_date', Carbon::parse($day)->subDay()->toDateString())->get(),
            ['sleep'],
            '23:00'
        );
        $sleepMinutes = $this->minutesBetween($previousSleep, $wakeTime);

        $commuteMinutes = $this->sumDuration($checkins, ['commute']);
        $studyMinutes = $this->sumDuration($checkins, ['duration'], ['学习']);
        $exerciseMinutes = $this->sumDuration($checkins, ['duration'], ['运动']);
        $gameMinutes = $this->sumDuration($checkins, ['duration'], ['玩游戏', '游戏']);
        $weight = $this->latestNumber($checkins, ['weight']);
        $mood = $this->latestMood($checkins);
        $score = $this->healthScore($sleepMinutes, $exerciseMinutes, $gameMinutes, $commuteMinutes, $mood['score']);

        $metrics = [
            'study_awake_ratio' => $this->ratio($studyMinutes, $awakeMinutes),
            'exercise_awake_ratio' => $this->ratio($exerciseMinutes, $awakeMinutes),
            'game_awake_ratio' => $this->ratio($gameMinutes, $awakeMinutes),
            'sleep_quality' => $this->sleepQuality($sleepMinutes, $sleepTime),
            'notes' => $checkins->pluck('note')->filter()->values(),
        ];

        $report = DailyHealthReport::firstOrNew(['report_date' => $day]);
        $report->fill([
            'report_date' => $day,
            'wake_time' => $wakeTime,
            'sleep_time' => $sleepTime,
            'awake_minutes' => $awakeMinutes,
            'sleep_minutes' => $sleepMinutes,
            'commute_minutes' => $commuteMinutes,
            'study_minutes' => $studyMinutes,
            'exercise_minutes' => $exerciseMinutes,
            'game_minutes' => $gameMinutes,
            'weight' => $weight,
            'mood_level' => $mood['level'],
            'mood_score' => $mood['score'],
            'health_score' => $score,
            'analysis_text' => $this->dailyText($sleepMinutes, $exerciseMinutes, $gameMinutes, $commuteMinutes, $mood['level'], $score),
            'metrics' => $metrics,
        ]);
        $report->save();

        Cache::forget('health:overview:'.$day);

        return $report;
    }

    public function rangeReport(Carbon $start, Carbon $end, string $label): array
    {
        $reports = $this->reports($start, $end);
        $days = max($start->diffInDays($end) + 1, 1);

        return [
            'label' => $label,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'avg_sleep_minutes' => round($reports->avg('sleep_minutes') ?: 0),
            'avg_commute_minutes' => round($reports->avg('commute_minutes') ?: 0),
            'avg_health_score' => round($reports->avg('health_score') ?: 0, 1),
            'study_minutes' => (int) $reports->sum('study_minutes'),
            'exercise_minutes' => (int) $reports->sum('exercise_minutes'),
            'game_minutes' => (int) $reports->sum('game_minutes'),
            'awake_minutes' => (int) $reports->sum('awake_minutes'),
            'study_ratio' => $this->ratio((int) $reports->sum('study_minutes'), (int) $reports->sum('awake_minutes')),
            'exercise_ratio' => $this->ratio((int) $reports->sum('exercise_minutes'), (int) $reports->sum('awake_minutes')),
            'game_ratio' => $this->ratio((int) $reports->sum('game_minutes'), (int) $reports->sum('awake_minutes')),
            'days' => $days,
            'items' => $reports->values(),
            'summary' => $this->rangeText($label, $reports),
        ];
    }

    public function chartData(?string $period = 'month'): array
    {
        $end = Carbon::today();
        $start = $period === 'week' ? $end->copy()->subDays(6) : $end->copy()->subDays(29);
        $reports = $this->reports($start, $end);

        return [
            'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
            'sleep' => $reports->pluck('sleep_minutes')->map(fn ($value) => round($value / 60, 1))->values(),
            'commute' => $reports->pluck('commute_minutes')->values(),
            'study' => $reports->pluck('study_minutes')->values(),
            'exercise' => $reports->pluck('exercise_minutes')->values(),
            'game' => $reports->pluck('game_minutes')->values(),
            'weight' => $reports->pluck('weight')->values(),
            'mood' => $reports->pluck('mood_score')->values(),
            'health' => $reports->pluck('health_score')->values(),
        ];
    }

    public function topic(string $topic, ?string $period = 'month'): array
    {
        $end = Carbon::today();
        $start = $period === 'week' ? $end->copy()->subDays(6) : $end->copy()->subDays(29);
        $reports = $this->reports($start, $end);

        return match ($topic) {
            'sleep' => $this->sleepTopic($reports, $start, $end),
            'commute' => $this->commuteTopic($reports, $start, $end),
            'time' => $this->timeTopic($reports, $start, $end),
            'body' => $this->bodyTopic($reports, $start, $end),
            default => $this->sleepTopic($reports, $start, $end),
        };
    }

    private function reports(Carbon $start, Carbon $end): Collection
    {
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $this->persistDailyReport($date->toDateString());
        }

        return DailyHealthReport::whereDate('report_date', '>=', $start->toDateString())
            ->whereDate('report_date', '<=', $end->toDateString())
            ->orderBy('report_date')
            ->get();
    }

    private function sleepTopic(Collection $reports, Carbon $start, Carbon $end): array
    {
        $avgSleep = round(($reports->avg('sleep_minutes') ?: 0) / 60, 1);
        $avgAwake = round(($reports->avg('awake_minutes') ?: 0) / 60, 1);

        return [
            'key' => 'sleep',
            'title' => '睡眠分析',
            'desc' => '查看起床、睡觉、睡眠时长和清醒时长的变化',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'cards' => [
                ['label' => '平均睡眠', 'value' => $avgSleep, 'unit' => '小时'],
                ['label' => '平均清醒', 'value' => $avgAwake, 'unit' => '小时'],
                ['label' => '最高健康评分', 'value' => (int) ($reports->max('health_score') ?: 0), 'unit' => '分'],
                ['label' => '记录天数', 'value' => $reports->count(), 'unit' => '天'],
            ],
            'summary' => "本周期平均睡眠 {$avgSleep} 小时，平均清醒 {$avgAwake} 小时。睡眠不足或睡觉时间波动较大时，个人状态可能更容易下降。",
            'charts' => [
                [
                    'id' => 'sleepHoursChart',
                    'title' => '睡眠 / 清醒时长',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '睡眠小时', 'data' => $reports->pluck('sleep_minutes')->map(fn ($value) => round($value / 60, 1))->values(), 'color' => '#1e9fff'],
                        ['label' => '清醒小时', 'data' => $reports->pluck('awake_minutes')->map(fn ($value) => round($value / 60, 1))->values(), 'color' => '#16baaa'],
                    ],
                ],
                [
                    'id' => 'sleepTimeChart',
                    'title' => '起床 / 睡觉时间',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '起床时间', 'data' => $reports->pluck('wake_time')->map(fn ($value) => $this->timeToHour((string) $value))->values(), 'color' => '#ffb800'],
                        ['label' => '睡觉时间', 'data' => $reports->pluck('sleep_time')->map(fn ($value) => $this->timeToHour((string) $value))->values(), 'color' => '#2f4056'],
                    ],
                ],
            ],
            'rows' => $reports->map(fn (DailyHealthReport $report) => [
                'date' => $report->report_date->toDateString(),
                'main' => '睡眠 '.round($report->sleep_minutes / 60, 1).' 小时',
                'sub' => '起床 '.substr((string) $report->wake_time, 0, 5).'，睡觉 '.substr((string) $report->sleep_time, 0, 5),
                'impact' => '健康评分 '.$report->health_score.'，睡眠质量 '.($report->metrics['sleep_quality'] ?? '-'),
                'note' => $report->analysis_text,
            ])->values(),
        ];
    }

    private function commuteTopic(Collection $reports, Carbon $start, Carbon $end): array
    {
        $avg = round($reports->avg('commute_minutes') ?: 0, 1);
        $max = (int) ($reports->max('commute_minutes') ?: 0);

        return [
            'key' => 'commute',
            'title' => '通勤分析',
            'desc' => '查看每天上班、下班通勤时长和异常备注',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'cards' => [
                ['label' => '平均通勤', 'value' => $avg, 'unit' => '分钟'],
                ['label' => '最长通勤', 'value' => $max, 'unit' => '分钟'],
                ['label' => '总通勤', 'value' => (int) $reports->sum('commute_minutes'), 'unit' => '分钟'],
                ['label' => '高压天数', 'value' => $reports->where('commute_minutes', '>', 90)->count(), 'unit' => '天'],
            ],
            'summary' => "本周期平均通勤 {$avg} 分钟。通勤超过 90 分钟的日期建议结合备注查看是否由天气、堵车或临时事件导致。",
            'charts' => [
                [
                    'id' => 'commuteMinutesChart',
                    'title' => '每日通勤分钟数',
                    'type' => 'bar',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '通勤分钟', 'data' => $reports->pluck('commute_minutes')->values(), 'color' => '#ffb800'],
                    ],
                ],
                [
                    'id' => 'commuteHealthChart',
                    'title' => '通勤与健康评分',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '通勤分钟', 'data' => $reports->pluck('commute_minutes')->values(), 'color' => '#ffb800'],
                        ['label' => '健康评分', 'data' => $reports->pluck('health_score')->values(), 'color' => '#16baaa'],
                    ],
                ],
            ],
            'rows' => $reports->map(fn (DailyHealthReport $report) => [
                'date' => $report->report_date->toDateString(),
                'main' => '通勤 '.$report->commute_minutes.' 分钟',
                'sub' => $report->commute_minutes > 90 ? '通勤偏长' : '通勤正常',
                'impact' => '健康评分 '.$report->health_score,
                'note' => collect($report->metrics['notes'] ?? [])->implode('；') ?: '-',
            ])->values(),
        ];
    }

    private function timeTopic(Collection $reports, Carbon $start, Carbon $end): array
    {
        $awake = (int) $reports->sum('awake_minutes');
        $study = (int) $reports->sum('study_minutes');
        $exercise = (int) $reports->sum('exercise_minutes');
        $game = (int) $reports->sum('game_minutes');
        $commute = (int) $reports->sum('commute_minutes');
        $other = max($awake - $study - $exercise - $game - $commute, 0);

        return [
            'key' => 'time',
            'title' => '学习运动游戏分析',
            'desc' => '查看学习、运动、游戏、通勤和其他时间在清醒时间中的占比',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'cards' => [
                ['label' => '学习占比', 'value' => $this->ratio($study, $awake), 'unit' => '%'],
                ['label' => '运动占比', 'value' => $this->ratio($exercise, $awake), 'unit' => '%'],
                ['label' => '游戏占比', 'value' => $this->ratio($game, $awake), 'unit' => '%'],
                ['label' => '清醒总时长', 'value' => round($awake / 60, 1), 'unit' => '小时'],
            ],
            'summary' => '本页用于观察清醒时间如何被分配。饼图中的“其他”代表没有被学习、运动、游戏、通勤等打卡项目覆盖的清醒时间。',
            'charts' => [
                [
                    'id' => 'timePieChart',
                    'title' => '清醒时间占比',
                    'type' => 'doughnut',
                    'labels' => ['学习', '运动', '游戏', '通勤', '其他'],
                    'values' => [$study, $exercise, $game, $commute, $other],
                    'colors' => ['#5fb878', '#ff5722', '#a233c6', '#ffb800', '#607d8b'],
                ],
                [
                    'id' => 'timeStackChart',
                    'title' => '学习 / 运动 / 游戏每日时长',
                    'type' => 'bar',
                    'stacked' => true,
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '学习', 'data' => $reports->pluck('study_minutes')->values(), 'color' => '#5fb878'],
                        ['label' => '运动', 'data' => $reports->pluck('exercise_minutes')->values(), 'color' => '#ff5722'],
                        ['label' => '游戏', 'data' => $reports->pluck('game_minutes')->values(), 'color' => '#a233c6'],
                    ],
                ],
                [
                    'id' => 'timeHealthChart',
                    'title' => '运动 / 游戏与健康评分',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '运动分钟', 'data' => $reports->pluck('exercise_minutes')->values(), 'color' => '#ff5722'],
                        ['label' => '游戏分钟', 'data' => $reports->pluck('game_minutes')->values(), 'color' => '#a233c6'],
                        ['label' => '健康评分', 'data' => $reports->pluck('health_score')->values(), 'color' => '#16baaa'],
                    ],
                ],
            ],
            'rows' => $reports->map(fn (DailyHealthReport $report) => [
                'date' => $report->report_date->toDateString(),
                'main' => "学习 {$report->study_minutes} / 运动 {$report->exercise_minutes} / 游戏 {$report->game_minutes} 分钟",
                'sub' => '清醒 '.round($report->awake_minutes / 60, 1).' 小时',
                'impact' => "学习占比 {$this->ratio($report->study_minutes, $report->awake_minutes)}%，运动占比 {$this->ratio($report->exercise_minutes, $report->awake_minutes)}%，游戏占比 {$this->ratio($report->game_minutes, $report->awake_minutes)}%",
                'note' => $report->analysis_text,
            ])->values(),
        ];
    }

    private function bodyTopic(Collection $reports, Carbon $start, Carbon $end): array
    {
        $weights = $reports->pluck('weight')->filter();
        $moods = $reports->pluck('mood_score')->filter();

        return [
            'key' => 'body',
            'title' => '体重状态分析',
            'desc' => '查看体重、个人状态评分与健康评分的变化',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'cards' => [
                ['label' => '平均体重', 'value' => $weights->isEmpty() ? '-' : round($weights->avg(), 1), 'unit' => $weights->isEmpty() ? '' : 'kg'],
                ['label' => '平均状态', 'value' => $moods->isEmpty() ? '-' : round($moods->avg(), 1), 'unit' => $moods->isEmpty() ? '' : '分'],
                ['label' => '平均健康评分', 'value' => round($reports->avg('health_score') ?: 0, 1), 'unit' => '分'],
                ['label' => '状态记录', 'value' => $moods->count(), 'unit' => '天'],
            ],
            'summary' => '本页用于观察体重和个人状态评分趋势，并结合睡眠、运动、游戏、通勤形成健康评分变化。',
            'charts' => [
                [
                    'id' => 'weightMoodChart',
                    'title' => '体重 / 状态评分',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '体重', 'data' => $reports->pluck('weight')->values(), 'color' => '#009688'],
                        ['label' => '状态评分', 'data' => $reports->pluck('mood_score')->values(), 'color' => '#e91e63'],
                    ],
                ],
                [
                    'id' => 'bodyHealthChart',
                    'title' => '状态评分 / 健康评分',
                    'type' => 'line',
                    'labels' => $reports->map(fn (DailyHealthReport $report) => $report->report_date->format('m-d'))->values(),
                    'datasets' => [
                        ['label' => '状态评分', 'data' => $reports->pluck('mood_score')->values(), 'color' => '#e91e63'],
                        ['label' => '健康评分', 'data' => $reports->pluck('health_score')->values(), 'color' => '#16baaa'],
                    ],
                ],
            ],
            'rows' => $reports->map(fn (DailyHealthReport $report) => [
                'date' => $report->report_date->toDateString(),
                'main' => '体重 '.($report->weight ? $report->weight.'kg' : '-').'，状态 '.($report->mood_level ?: '-'),
                'sub' => $report->mood_score ? '状态评分 '.$report->mood_score.' 分' : '未记录状态评分',
                'impact' => '健康评分 '.$report->health_score,
                'note' => collect($report->metrics['notes'] ?? [])->implode('；') ?: '-',
            ])->values(),
        ];
    }

    private function firstTime(Collection $checkins, array $types, string $default): string
    {
        $checkin = $checkins->first(fn (Checkin $item) => in_array($item->habit?->habit_type, $types, true));

        return $checkin ? substr((string) $checkin->checkin_time, 0, 5) : $default;
    }

    private function sumDuration(Collection $checkins, array $types, array $names = []): int
    {
        return (int) $checkins
            ->filter(function (Checkin $item) use ($types, $names) {
                $typeMatched = in_array($item->habit?->habit_type, $types, true);
                $nameMatched = empty($names) || collect($names)->contains(fn ($name) => str_contains((string) $item->habit?->name, $name));

                return $typeMatched && $nameMatched;
            })
            ->sum(fn (Checkin $item) => $item->duration_minutes ?: 0);
    }

    private function latestNumber(Collection $checkins, array $types): ?float
    {
        $checkin = $checkins->filter(fn (Checkin $item) => in_array($item->habit?->habit_type, $types, true))->last();

        return $checkin?->value_number ? (float) $checkin->value_number : null;
    }

    private function latestMood(Collection $checkins): array
    {
        $checkin = $checkins->filter(fn (Checkin $item) => $item->habit?->habit_type === 'mood')->last();

        return [
            'level' => $checkin?->value_text,
            'score' => $checkin?->mood_score,
        ];
    }

    private function minutesBetween(string $start, string $end): int
    {
        $startTime = Carbon::createFromFormat('H:i', $start);
        $endTime = Carbon::createFromFormat('H:i', $end);
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        return $startTime->diffInMinutes($endTime);
    }

    private function timeToHour(string $time): ?float
    {
        if (! preg_match('/^(\d{2}):(\d{2})/', $time, $matches)) {
            return null;
        }

        return round(((int) $matches[1]) + ((int) $matches[2]) / 60, 2);
    }

    private function ratio(int $part, int $total): float
    {
        return $total <= 0 ? 0 : round($part / $total * 100, 1);
    }

    private function sleepQuality(int $sleepMinutes, string $sleepTime): string
    {
        $sleepAt = Carbon::createFromFormat('H:i', $sleepTime);
        if ($sleepMinutes >= 420 && $sleepMinutes <= 540 && $sleepAt->hour < 24) {
            return '较好';
        }
        if ($sleepMinutes < 360) {
            return '偏少';
        }
        if ($sleepMinutes > 570) {
            return '偏多';
        }

        return '一般';
    }

    private function healthScore(int $sleepMinutes, int $exerciseMinutes, int $gameMinutes, int $commuteMinutes, ?int $moodScore): int
    {
        $score = 60;
        if ($sleepMinutes >= 420 && $sleepMinutes <= 540) $score += 15;
        if ($sleepMinutes < 360) $score -= 15;
        if ($exerciseMinutes >= 30) $score += 12;
        if ($gameMinutes > 120) $score -= 10;
        if ($commuteMinutes > 120) $score -= 8;
        if ($moodScore) $score += ($moodScore - 3) * 5;

        return max(0, min(100, $score));
    }

    private function dailyText(int $sleepMinutes, int $exerciseMinutes, int $gameMinutes, int $commuteMinutes, ?string $moodLevel, int $score): string
    {
        $parts = ["今日健康评分 {$score} 分。"];
        $parts[] = '睡眠约 '.round($sleepMinutes / 60, 1).' 小时。';
        if ($exerciseMinutes >= 30) $parts[] = "运动 {$exerciseMinutes} 分钟，对状态有正向帮助。";
        if ($gameMinutes > 120) $parts[] = "游戏 {$gameMinutes} 分钟偏长，可能挤压睡眠和恢复时间。";
        if ($commuteMinutes > 90) $parts[] = "通勤 {$commuteMinutes} 分钟较长，可结合备注查看原因。";
        if ($moodLevel) $parts[] = "个人状态：{$moodLevel}。";

        return implode('', $parts);
    }

    private function rangeText(string $label, Collection $reports): string
    {
        if ($reports->isEmpty()) {
            return "{$label}暂无可分析数据。";
        }

        $sleep = round(($reports->avg('sleep_minutes') ?: 0) / 60, 1);
        $exercise = round($reports->avg('exercise_minutes') ?: 0);
        $game = round($reports->avg('game_minutes') ?: 0);
        $score = round($reports->avg('health_score') ?: 0, 1);

        return "{$label}平均睡眠 {$sleep} 小时，平均运动 {$exercise} 分钟，平均游戏 {$game} 分钟，综合健康评分 {$score}。";
    }
}
