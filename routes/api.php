<?php

use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::apiResource('habits', HabitController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('checkins', CheckinController::class)->only(['index', 'store', 'update', 'destroy']);

Route::prefix('stats')->group(function () {
    Route::get('overview', [StatsController::class, 'overview']);
    Route::get('daily', [StatsController::class, 'daily']);
    Route::get('weekly', [StatsController::class, 'weekly']);
    Route::get('monthly', [StatsController::class, 'monthly']);
    Route::get('habit/{id}', [StatsController::class, 'habit']);
    Route::get('timeline', [StatsController::class, 'timeline']);
    Route::get('summary', [StatsController::class, 'summary']);
    Route::get('health', [StatsController::class, 'health']);
    Route::get('comprehensive', [StatsController::class, 'comprehensive']);
});
