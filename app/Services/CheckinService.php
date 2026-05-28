<?php

namespace App\Services;

use App\Models\Checkin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CheckinService
{
    public function list(?string $date = null): Collection
    {
        return Checkin::with('habit')
            ->when($date, fn ($query) => $query->whereDate('checkin_date', $date))
            ->orderByDesc('checkin_date')
            ->orderBy('checkin_time')
            ->get();
    }

    public function create(array $data): Checkin
    {
        $data = $this->normalize($data);
        $checkin = Checkin::updateOrCreate(
            ['habit_id' => $data['habit_id'], 'checkin_date' => $data['checkin_date']],
            $data
        );

        $this->clearStatsCache($checkin);
        $this->persistSummaries([$checkin->checkin_date?->toDateString()]);
        app(HealthAnalysisService::class)->persistDailyReport($checkin->checkin_date?->toDateString());

        return $checkin->load('habit');
    }

    public function update(Checkin $checkin, array $data): Checkin
    {
        $oldDate = $checkin->checkin_date?->toDateString();
        $data = $this->normalize($data);
        $checkin->update($data);
        $checkin->refresh()->load('habit');
        $this->clearStatsCache($checkin, $oldDate);
        $this->persistSummaries([$oldDate, $checkin->checkin_date?->toDateString()]);
        app(HealthAnalysisService::class)->persistDailyReport($oldDate);
        app(HealthAnalysisService::class)->persistDailyReport($checkin->checkin_date?->toDateString());

        return $checkin;
    }

    public function delete(Checkin $checkin): void
    {
        $date = $checkin->checkin_date?->toDateString();
        $checkin->delete();
        $this->clearStatsCache($checkin);
        $this->persistSummaries([$date]);
        app(HealthAnalysisService::class)->persistDailyReport($date);
    }

    public function clearStatsCache(Checkin $checkin, ?string $extraDate = null): void
    {
        $dates = array_filter([$checkin->checkin_date?->toDateString(), $extraDate]);

        Cache::forget('stats:overview');
        Cache::forget('stats:summary');
        Cache::forget('stats:habit:'.$checkin->habit_id);

        foreach ($dates as $date) {
            $carbon = Carbon::parse($date);
            Cache::forget('stats:daily:'.$carbon->toDateString());
            Cache::forget('stats:timeline:'.$carbon->toDateString());
            Cache::forget('stats:weekly:'.$carbon->isoWeekYear().'-W'.$carbon->isoWeek());
            Cache::forget('stats:monthly:'.$carbon->format('Y-m'));
        }
    }

    private function normalize(array $data): array
    {
        if (isset($data['checkin_time']) && strlen($data['checkin_time']) === 5) {
            $data['checkin_time'] .= ':00';
        }

        foreach (['start_time', 'end_time'] as $field) {
            if (isset($data[$field]) && strlen($data[$field]) === 5) {
                $data[$field] .= ':00';
            }
        }

        if (empty($data['duration_minutes']) && ! empty($data['start_time']) && ! empty($data['end_time'])) {
            $start = Carbon::createFromFormat('H:i:s', $data['start_time']);
            $end = Carbon::createFromFormat('H:i:s', $data['end_time']);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $data['duration_minutes'] = $start->diffInMinutes($end);
        }

        return $data;
    }

    private function persistSummaries(array $dates): void
    {
        $dates = array_unique(array_filter($dates));
        foreach ($dates as $date) {
            app(StatsService::class)->persistDailySummary($date);
        }
    }
}
