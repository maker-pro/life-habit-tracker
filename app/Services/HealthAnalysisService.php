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
