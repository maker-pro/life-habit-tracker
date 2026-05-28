<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'summary_date',
        'completion_rate',
        'summary_text',
    ];

    protected $casts = [
        'completion_rate' => 'float',
    ];
}
