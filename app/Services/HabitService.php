<?php

namespace App\Services;

use App\Models\Habit;
use Illuminate\Database\Eloquent\Collection;

class HabitService
{
    public function list(bool $onlyActive = false): Collection
    {
        return Habit::query()
            ->when($onlyActive, fn ($query) => $query->where('status', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): Habit
    {
        $data = $this->normalize($data);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Habit::max('sort_order') + 1);

        return Habit::create($data);
    }

    public function update(Habit $habit, array $data): Habit
    {
        $habit->update($this->normalize($data));

        return $habit->refresh();
    }

    public function delete(Habit $habit): void
    {
        $habit->delete();
    }

    private function normalize(array $data): array
    {
        $data['habit_type'] = $data['habit_type'] ?? 'normal';

        if (isset($data['suggested_time']) && strlen($data['suggested_time']) === 5) {
            $data['suggested_time'] .= ':00';
        }

        foreach (['is_daily', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }
}
