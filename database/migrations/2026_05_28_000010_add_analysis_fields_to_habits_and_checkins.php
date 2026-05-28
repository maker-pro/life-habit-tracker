<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->string('habit_type', 30)->default('normal')->after('category')->index();
        });

        Schema::table('checkins', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('checkin_time');
            $table->time('end_time')->nullable()->after('start_time');
            $table->unsignedInteger('duration_minutes')->nullable()->after('end_time');
            $table->decimal('value_number', 8, 2)->nullable()->after('duration_minutes');
            $table->string('value_text', 50)->nullable()->after('value_number');
            $table->unsignedTinyInteger('mood_score')->nullable()->after('value_text');
            $table->json('meta')->nullable()->after('mood_score');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropColumn([
                'start_time',
                'end_time',
                'duration_minutes',
                'value_number',
                'value_text',
                'mood_score',
                'meta',
            ]);
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('habit_type');
        });
    }
};
