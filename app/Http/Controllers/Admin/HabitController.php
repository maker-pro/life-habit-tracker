<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HabitRequest;
use App\Models\Checkin;
use App\Models\Habit;
use App\Services\HabitService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function __construct(private readonly HabitService $habitService)
    {
    }

    public function index()
    {
        return view('admin.habits.index', ['habits' => $this->habitService->list()]);
    }

    public function create()
    {
        return view('admin.habits.create');
    }

    public function store(HabitRequest $request)
    {
        $this->habitService->create($request->validated());

        return redirect()->route('admin.habits.index')->with('success', '事项创建成功');
    }

    public function edit(Habit $habit)
    {
        return view('admin.habits.edit', ['habit' => $habit]);
    }

    public function show(Request $request, Habit $habit)
    {
        $start = Carbon::parse($request->query('start_date', Carbon::today()->subDays(29)->toDateString()))->toDateString();
        $end = Carbon::parse($request->query('end_date', Carbon::today()->toDateString()))->toDateString();

        $baseQuery = Checkin::with('habit')
            ->where('habit_id', $habit->id)
            ->whereDate('checkin_date', '>=', $start)
            ->whereDate('checkin_date', '<=', $end);

        $records = (clone $baseQuery)
            ->orderByDesc('checkin_date')
            ->orderByDesc('checkin_time')
            ->paginate(20)
            ->withQueryString();

        $allRecords = (clone $baseQuery)
            ->orderBy('checkin_date')
            ->orderBy('checkin_time')
            ->get();

        return view('admin.habits.show', [
            'habit' => $habit,
            'records' => $records,
            'start' => $start,
            'end' => $end,
            'summary' => [
                'total' => $allRecords->count(),
                'days' => $allRecords->pluck('checkin_date')->unique()->count(),
                'avg_duration' => round((float) $allRecords->whereNotNull('duration_minutes')->avg('duration_minutes'), 1),
                'latest_date' => optional($allRecords->last()?->checkin_date)->toDateString(),
            ],
            'chart' => $this->buildRecordChart($habit, $allRecords),
        ]);
    }

    public function update(HabitRequest $request, Habit $habit)
    {
        $this->habitService->update($habit, $request->validated());

        return redirect()->route('admin.habits.index')->with('success', '事项更新成功');
    }

    public function destroy(Habit $habit)
    {
        $this->habitService->delete($habit);

        return redirect()->route('admin.habits.index')->with('success', '事项删除成功');
    }

    private function buildRecordChart(Habit $habit, $records): array
    {
        $label = match ($habit->habit_type) {
            'commute' => '通勤分钟',
            'duration' => '持续分钟',
            'weight' => '体重',
            'mood' => '状态评分',
            default => '打卡时间',
        };

        return [
            'label' => $label,
            'labels' => $records->map(fn (Checkin $checkin) => $checkin->checkin_date->format('m-d'))->values(),
            'values' => $records->map(function (Checkin $checkin) use ($habit) {
                return match ($habit->habit_type) {
                    'commute', 'duration' => $checkin->duration_minutes,
                    'weight' => $checkin->value_number ? (float) $checkin->value_number : null,
                    'mood' => $checkin->mood_score,
                    default => $this->timeToHour((string) $checkin->checkin_time),
                };
            })->values(),
        ];
    }

    private function timeToHour(string $time): ?float
    {
        if (! preg_match('/^(\d{2}):(\d{2})/', $time, $matches)) {
            return null;
        }

        return round(((int) $matches[1]) + ((int) $matches[2]) / 60, 2);
    }
}
