<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'habit_type',
        'icon',
        'color',
        'suggested_time',
        'is_daily',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'is_daily' => 'boolean',
        'status' => 'boolean',
    ];

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }
}
