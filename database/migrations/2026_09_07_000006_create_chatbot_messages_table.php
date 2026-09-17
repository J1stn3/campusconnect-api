<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->text('response');
            $table->string('conversation_id')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();
            $table->index('conversation_id');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
