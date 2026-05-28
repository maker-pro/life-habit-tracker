<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckinRequest;
use App\Models\Checkin;
use App\Models\Habit;
use App\Services\CheckinService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(private readonly CheckinService $checkinService)
    {
    }

    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        return view('admin.checkins.index', [
            'date' => $date,
            'habits' => Habit::where('status', true)->orderBy('sort_order')->get(),
            'checkins' => $this->checkinService->list($date),
        ]);
    }

    public function store(CheckinRequest $request)
    {
        $this->checkinService->create($request->validated());

        return redirect()
            ->route('admin.checkins.index', ['date' => $request->input('checkin_date')])
            ->with('success', '打卡记录已保存');
    }

    public function update(CheckinRequest $request, Checkin $checkin)
    {
        $this->checkinService->update($checkin, $request->validated());

        return redirect()
            ->route('admin.checkins.index', ['date' => $checkin->checkin_date->toDateString()])
            ->with('success', '打卡记录已更新');
    }

    public function destroy(Checkin $checkin)
    {
        $date = $checkin->checkin_date->toDateString();
        $this->checkinService->delete($checkin);

        return redirect()
            ->route('admin.checkins.index', ['date' => $date])
            ->with('success', '打卡记录已删除');
    }
}
