<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'habit_id' => [$required, 'integer', 'exists:habits,id'],
            'checkin_date' => [$required, 'date_format:Y-m-d'],
            'checkin_time' => [$required, 'date_format:H:i'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'value_number' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'value_text' => ['nullable', 'string', 'max:50'],
            'mood_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'meta' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'habit_id.required' => '请选择打卡事项',
            'habit_id.exists' => '打卡事项不存在',
            'checkin_date.required' => '请选择打卡日期',
            'checkin_date.date_format' => '打卡日期格式必须为 YYYY-MM-DD',
            'checkin_time.required' => '请选择打卡时间',
            'checkin_time.date_format' => '打卡时间格式必须为 HH:mm',
        ];
    }
}
