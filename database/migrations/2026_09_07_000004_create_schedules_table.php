<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code', 20);
            $table->string('subject_name');
            $table->string('instructor');
            $table->string('room');
            $table->enum('day', [
                'Monday', 'Tuesday', 'Wednesday',
                'Thursday', 'Friday', 'Saturday', 'Sunday',
            ]);
            $table->time('time_start');
            $table->time('time_end');
            $table->unsignedTinyInteger('units')->default(3);
            $table->string('section', 20)->nullable();
            $table->enum('type', ['Lecture', 'Laboratory', 'Lecture/Laboratory'])->default('Lecture');
            $table->string('semester', 20)->default('1st');
            $table->string('school_year', 20)->default('2025-2026');
            $table->timestamps();
            $table->index(['user_id', 'day']);
            $table->index(['user_id', 'school_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
