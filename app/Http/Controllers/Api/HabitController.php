<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HabitRequest;
use App\Http\Resources\HabitResource;
use App\Models\Habit;
use App\Services\HabitService;
use Illuminate\Http\JsonResponse;

class HabitController extends Controller
{
    public function __construct(private readonly HabitService $habitService)
    {
    }

    public function index(): JsonResponse
    {
        return $this->success(HabitResource::collection($this->habitService->list())->resolve());
    }

    public function store(HabitRequest $request): JsonResponse
    {
        $habit = $this->habitService->create($request->validated());

        return $this->success((new HabitResource($habit))->resolve(), 'success', 201);
    }

    public function update(HabitRequest $request, Habit $habit): JsonResponse
    {
        $habit = $this->habitService->update($habit, $request->validated());

        return $this->success((new HabitResource($habit))->resolve());
    }

    public function destroy(Habit $habit): JsonResponse
    {
        $this->habitService->delete($habit);

        return $this->success();
    }

    private function success(mixed $data = [], string $message = 'success', int $httpStatus = 200): JsonResponse
    {
        return response()->json(['code' => 200, 'message' => $message, 'data' => $data], $httpStatus);
    }
}
