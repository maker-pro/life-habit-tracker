<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'habit_type' => $this->habit_type ?? 'normal',
            'icon' => $this->icon,
            'color' => $this->color,
            'suggested_time' => $this->suggested_time ? substr((string) $this->suggested_time, 0, 5) : null,
            'is_daily' => (bool) $this->is_daily,
            'sort_order' => $this->sort_order,
            'status' => (bool) $this->status,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
