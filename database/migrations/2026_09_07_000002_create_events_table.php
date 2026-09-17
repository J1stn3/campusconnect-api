<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('category', 100);
            $table->enum('status', ['upcoming', 'ongoing', 'past', 'cancelled'])->default('upcoming');
            $table->unsignedInteger('capacity')->default(100);
            $table->boolean('is_free')->default(true);
            $table->string('contact_email')->nullable();
            $table->string('image_url')->nullable();
            $table->date('registration_deadline')->nullable();
            $table->timestamps();
            $table->index('date');
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
