<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HabitRequest;
use App\Models\Habit;
use App\Services\HabitService;

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
}
