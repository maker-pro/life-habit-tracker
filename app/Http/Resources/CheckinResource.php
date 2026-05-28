<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'habit_id' => $this->habit_id,
            'habit' => $this->whenLoaded('habit', fn () => new HabitResource($this->habit)),
            'checkin_date' => optional($this->checkin_date)->toDateString(),
            'checkin_time' => substr((string) $this->checkin_time, 0, 5),
            'start_time' => $this->start_time ? substr((string) $this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr((string) $this->end_time, 0, 5) : null,
            'duration_minutes' => $this->duration_minutes,
            'value_number' => $this->value_number,
            'value_text' => $this->value_text,
            'mood_score' => $this->mood_score,
            'meta' => $this->meta,
            'note' => $this->note,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
