<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits')->cascadeOnDelete();
            $table->date('checkin_date');
            $table->time('checkin_time');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'checkin_date']);
            $table->index(['checkin_date', 'checkin_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
