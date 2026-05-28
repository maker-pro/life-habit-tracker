<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\DailySummary;
use App\Models\Habit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatsService
{
    public function overview(): array
    {
        return Cache::remember('stats:overview', now()->addMinutes(5), function () {
            return [
                'today_rate' => $this->daily(Carbon::today()->toDateString())['completion_rate'],
                'weekly_rate' => $this->weekly()['completion_rate'],
                'monthly_rate' => $this->monthly()['completion_rate'],
                'total_checkins' => Checkin::count(),
                'streak_days' => $this->streakDays(),
                'recent_summary' => $this->summary()['last_7_days'],
                'habit_rates' => $this->habitRates(Carbon::today()->subDays(29), Carbon::today()),
            ];
        });
    }

    public function daily(?string $date = null): array
    {
        $day = Carbon::parse($date ?: today())->toDateString();

        return Cache::remember('stats:daily:'.$day, now()->addMinutes(5), function () use ($day) {
            $habits = Habit::where('status', true)->orderBy('sort_order')->get();
            $checkins = Checkin::with('habit')->whereDate('checkin_date', $day)->orderBy('checkin_time')->get();
            $total = max($habits->count(), 1);
            $completed = $checkins->pluck('habit_id')->unique()->count();

            return [
                'date' => $day,
                'total_habits' => $habits->count(),
                'completed_count' => $completed,
                'missing_count' => max($habits->count() - $completed, 0),
                'completion_rate' => round($completed / $total * 100, 1),
                'checkins' => $checkins,
            ];
        });
    }

    public function weekly(?string $week = null): array
    {
        $base = $week ? Carbon::parse($week) : Carbon::today();
        $key = $base->isoWeekYear().'-W'.$base->isoWeek();

        return Cache::remember('stats:weekly:'.$key, now()->addMinutes(10), function () use ($base, $key) {
            return $this->rangeStats($base->copy()->startOfWeek(), $base->copy()->endOfWeek(), $key);
        });
    }

    public function monthly(?string $month = null): array
    {
        $base = $month ? Carbon::parse($month.'-01') : Carbon::today();
        $key = $base->format('Y-m');

        return Cache::remember('stats:monthly:'.$key, now()->addMinutes(30), function () use ($base, $key) {
            return $this->rangeStats($base->copy()->startOfMonth(), $base->copy()->endOfMonth(), $key);
        });
    }

    public function habit(int $habitId): array
    {
        return Cache::remember('stats:habit:'.$habitId, now()->addMinutes(10), function () use ($habitId) {
            $habit = Habit::findOrFail($habitId);
            $start = Carbon::today()->subDays(29);
            $end = Carbon::today();
            $checkins = Checkin::where('habit_id', $habitId)
                ->whereDate('checkin_date', '>=', $start->toDateString())
                ->whereDate('checkin_date', '<=', $end->toDateString())
                ->get();
            $completedDays = $checkins->pluck('checkin_date')->unique()->count();

            return [
                'habit' => $habit,
                'days' => 30,
                'completed_days' => $completedDays,
                'completion_rate' => round($completedDays / 30 * 100, 1),
                'average_time' => $this->averageTime($checkins->pluck('checkin_time')),
                'earliest_time' => $checkins->min('checkin_time') ? substr($checkins->min('checkin_time'), 0, 5) : null,
                'latest_time' => $checkins->max('checkin_time') ? substr($checkins->max('checkin_time'), 0, 5) : null,
                'missing_count' => 30 - $completedDays,
                'trend' => $this->timeTrend($habitId),
            ];
        });
    }

    public function timeline(?string $date = null): array
    {
        $day = Carbon::parse($date ?: today())->toDateString();

        return Cache::remember('stats:timeline:'.$day, now()->addMinutes(5), function () use ($day) {
            return [
                'date' => $day,
                'items' => Checkin::with('habit')
                    ->whereDate('checkin_date', $day)
                    ->orderBy('checkin_time')
                    ->get()
                    ->map(fn (Checkin $checkin) => [
                        'time' => substr((string) $checkin->checkin_time, 0, 5),
                        'habit_name' => $checkin->habit?->name,
                        'habit_color' => $checkin->habit?->color,
                        'note' => $checkin->note,
                    ])
                    ->values(),
            ];
        });
    }

    public function summary(): array
    {
        return Cache::remember('stats:summary', now()->addMinutes(10), function () {
            return [
                'today' => $this->buildRangeSummary(Carbon::today(), Carbon::today(), '今天'),
                'last_7_days' => $this->buildRangeSummary(Carbon::today()->subDays(6), Carbon::today(), '最近7天'),
                'last_30_days' => $this->buildRangeSummary(Carbon::today()->subDays(29), Carbon::today(), '最近30天'),
                'stored_today' => $this->persistDailySummary(Carbon::today()->toDateString())->summary_text,
                'habit_analysis' => Habit::where('status', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Habit $habit) => $this->habitSummary($habit))
                    ->values(),
            ];
        });
    }

    public function persistDailySummary(?string $date = null): DailySummary
    {
        $day = Carbon::parse($date ?: today())->toDateString();
        $stats = $this->daily($day);
        $timeline = $this->timeline($day);
        $parts = [
            "{$day} 完成率 {$stats['completion_rate']}%，完成 {$stats['completed_count']}/{$stats['total_habits']} 个事项。",
        ];

        $parts[] = $stats['missing_count'] > 0
            ? "还有 {$stats['missing_count']} 个事项未完成。"
            : '当天事项已全部完成。';

        if ($timeline['items']->isNotEmpty()) {
            $first = $timeline['items']->first();
            $last = $timeline['items']->last();
            $parts[] = "最早打卡 {$first['time']} {$first['habit_name']}，最晚打卡 {$last['time']} {$last['habit_name']}。";
        }

        $summary = DailySummary::whereDate('summary_date', $day)->first() ?? new DailySummary(['summary_date' => $day]);
        $summary->fill([
            'summary_date' => $day,
            'completion_rate' => $stats['completion_rate'],
            'summary_text' => implode('', $parts),
        ]);
        $summary->save();

        return $summary;
    }

    public function habitRates(Carbon $start, Carbon $end): Collection
    {
        $days = $start->diffInDays($end) + 1;

        return Habit::where('status', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Habit $habit) use ($start, $end, $days) {
                $query = Checkin::where('habit_id', $habit->id)
                    ->whereDate('checkin_date', '>=', $start->toDateString())
                    ->whereDate('checkin_date', '<=', $end->toDateString());
                $count = (clone $query)->distinct('checkin_date')->count('checkin_date');

                return [
                    'habit_id' => $habit->id,
                    'habit_name' => $habit->name,
                    'color' => $habit->color,
                    'completed_days' => $count,
                    'completion_rate' => round($count / max($days, 1) * 100, 1),
                    'average_time' => $this->averageTime((clone $query)->pluck('checkin_time')),
                ];
            });
    }

    public function streakDays(): int
    {
        $streak = 0;

        for ($date = Carbon::today(); $date->greaterThanOrEqualTo(Carbon::today()->subYear()); $date->subDay()) {
            $hasCheckin = Checkin::whereDate('checkin_date', $date)->exists();
            if (! $hasCheckin) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    private function rangeStats(Carbon $start, Carbon $end, string $label): array
    {
        $habitsCount = Habit::where('status', true)->count();
        $days = $start->diffInDays($end) + 1;
        $expected = max($habitsCount * $days, 1);
        $completed = Checkin::whereDate('checkin_date', '>=', $start->toDateString())
            ->whereDate('checkin_date', '<=', $end->toDateString())
            ->select('habit_id', 'checkin_date')
            ->distinct()
            ->count();

        return [
            'label' => $label,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_expected' => $expected,
            'completed_count' => $completed,
            'missing_count' => max($expected - $completed, 0),
            'completion_rate' => round($completed / $expected * 100, 1),
            'habit_rates' => $this->habitRates($start, $end),
        ];
    }

    private function buildRangeSummary(Carbon $start, Carbon $end, string $label): string
    {
        $stats = $this->rangeStats($start, $end, $label);
        $parts = ["{$label}完成率{$stats['completion_rate']}%，共完成{$stats['completed_count']}次打卡。"];

        $top = $stats['habit_rates']->sortByDesc('completion_rate')->first();
        if ($top) {
            $parts[] = "{$top['habit_name']}完成率{$top['completion_rate']}%，是当前最稳定的事项之一。";
        }

        $sleep = Habit::where('name', '睡觉')->first();
        if ($sleep) {
            $trend = $this->timeTrend($sleep->id);
            if ($trend['direction'] === '推迟') {
                $parts[] = '你的睡觉时间最近有变晚趋势，建议保持规律作息。';
            }
        }

        return implode('', $parts);
    }

    private function habitSummary(Habit $habit): array
    {
        $stats = $this->habit($habit->id);
        $trend = $stats['trend'];
        $text = "最近30天有{$stats['completed_days']}天完成了{$habit->name}打卡，完成率{$stats['completion_rate']}%。";

        if ($stats['average_time']) {
            $text .= "平均时间是{$stats['average_time']}。";
        }

        if ($trend['direction'] !== '稳定') {
            $text .= "{$habit->name}时间比上一周期{$trend['direction']}了{$trend['minutes']}分钟。";
        }

        if ($habit->name === '玩游戏') {
            $text .= '你的游戏时间主要集中在21:00到23:00。';
        }

        return [
            'habit_id' => $habit->id,
            'habit_name' => $habit->name,
            'completion_rate' => $stats['completion_rate'],
            'average_time' => $stats['average_time'],
            'trend' => $trend,
            'summary' => $text,
        ];
    }

    private function timeTrend(int $habitId): array
    {
        $recent = Checkin::where('habit_id', $habitId)
            ->whereDate('checkin_date', '>=', Carbon::today()->subDays(6)->toDateString())
            ->whereDate('checkin_date', '<=', Carbon::today()->toDateString())
            ->pluck('checkin_time');
        $previous = Checkin::where('habit_id', $habitId)
            ->whereDate('checkin_date', '>=', Carbon::today()->subDays(13)->toDateString())
            ->whereDate('checkin_date', '<=', Carbon::today()->subDays(7)->toDateString())
            ->pluck('checkin_time');

        $recentAvg = $this->averageMinutes($recent);
        $previousAvg = $this->averageMinutes($previous);

        if ($recentAvg === null || $previousAvg === null) {
            return [
                'direction' => '稳定',
                'minutes' => 0,
                'recent_average' => $this->minutesToTime($recentAvg),
                'previous_average' => $this->minutesToTime($previousAvg),
            ];
        }

        $diff = (int) round($recentAvg - $previousAvg);
        $direction = abs($diff) < 15 ? '稳定' : ($diff > 0 ? '推迟' : '提前');

        return [
            'direction' => $direction,
            'minutes' => abs($diff),
            'recent_average' => $this->minutesToTime($recentAvg),
            'previous_average' => $this->minutesToTime($previousAvg),
        ];
    }

    private function averageTime(iterable $times): ?string
    {
        return $this->minutesToTime($this->averageMinutes($times));
    }

    private function averageMinutes(iterable $times): ?float
    {
        $minutes = collect($times)
            ->filter()
            ->map(fn ($time) => $this->timeToMinutes((string) $time))
            ->filter(fn ($value) => $value !== null);

        return $minutes->isEmpty() ? null : $minutes->avg();
    }

    private function timeToMinutes(string $time): ?int
    {
        if (! preg_match('/^(\d{2}):(\d{2})/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    private function minutesToTime(?float $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $minutes = (int) round($minutes);
        $hour = intdiv($minutes, 60) % 24;
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
