<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    use HasFactory;

    protected $fillable = [
        'habit_id',
        'checkin_date',
        'checkin_time',
        'start_time',
        'end_time',
        'duration_minutes',
        'value_number',
        'value_text',
        'mood_score',
        'meta',
        'note',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'value_number' => 'decimal:2',
        'meta' => 'array',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
