<?php

use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\HabitController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('habits', HabitController::class);
        Route::resource('checkins', CheckinController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('analysis', [AnalysisController::class, 'index'])->name('analysis.index');
        Route::get('analysis/{topic}', [AnalysisController::class, 'topic'])->name('analysis.topic');
        Route::get('reports/timeline', [ReportController::class, 'timeline'])->name('reports.timeline');
        Route::get('reports/calendar', [ReportController::class, 'calendar'])->name('reports.calendar');
    });
});
