<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('category', 100);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_pinned')->default(false);
            $table->string('image_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('category');
            $table->index('is_pinned');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
