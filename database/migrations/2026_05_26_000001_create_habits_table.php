<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('category', 50)->default('日常');
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->default('#16baaa');
            $table->time('suggested_time')->nullable();
            $table->boolean('is_daily')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
