<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckinRequest;
use App\Http\Resources\CheckinResource;
use App\Models\Checkin;
use App\Services\CheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(private readonly CheckinService $checkinService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $checkins = $this->checkinService->list($request->query('date'));

        return $this->success(CheckinResource::collection($checkins)->resolve());
    }

    public function store(CheckinRequest $request): JsonResponse
    {
        $checkin = $this->checkinService->create($request->validated());

        return $this->success((new CheckinResource($checkin))->resolve(), 'success', 201);
    }

    public function update(CheckinRequest $request, Checkin $checkin): JsonResponse
    {
        $checkin = $this->checkinService->update($checkin, $request->validated());

        return $this->success((new CheckinResource($checkin))->resolve());
    }

    public function destroy(Checkin $checkin): JsonResponse
    {
        $this->checkinService->delete($checkin);

        return $this->success();
    }

    private function success(mixed $data = [], string $message = 'success', int $httpStatus = 200): JsonResponse
    {
        return response()->json(['code' => 200, 'message' => $message, 'data' => $data], $httpStatus);
    }
}
