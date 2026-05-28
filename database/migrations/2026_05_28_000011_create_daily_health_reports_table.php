<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_health_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->time('wake_time')->nullable();
            $table->time('sleep_time')->nullable();
            $table->unsignedInteger('awake_minutes')->default(0);
            $table->unsignedInteger('sleep_minutes')->default(0);
            $table->unsignedInteger('commute_minutes')->default(0);
            $table->unsignedInteger('study_minutes')->default(0);
            $table->unsignedInteger('exercise_minutes')->default(0);
            $table->unsignedInteger('game_minutes')->default(0);
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('mood_level', 50)->nullable();
            $table->unsignedTinyInteger('mood_score')->nullable();
            $table->unsignedTinyInteger('health_score')->default(60);
            $table->text('analysis_text')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_health_reports');
    }
};
