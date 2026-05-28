<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyHealthReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'wake_time',
        'sleep_time',
        'awake_minutes',
        'sleep_minutes',
        'commute_minutes',
        'study_minutes',
        'exercise_minutes',
        'game_minutes',
        'weight',
        'mood_level',
        'mood_score',
        'health_score',
        'analysis_text',
        'metrics',
    ];

    protected $casts = [
        'report_date' => 'date',
        'weight' => 'decimal:2',
        'metrics' => 'array',
    ];
}
