<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'habit_type' => ['nullable', 'string', 'in:normal,commute,duration,weight,mood,sleep,wake'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'suggested_time' => ['nullable', 'date_format:H:i'],
            'is_daily' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '事项名称不能为空',
            'name.max' => '事项名称不能超过50个字符',
            'suggested_time.date_format' => '建议时间格式必须为 HH:mm',
        ];
    }
}
