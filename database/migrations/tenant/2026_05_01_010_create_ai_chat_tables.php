<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user AI conversation history. Conversations belong to a SchoolUser
 * (so each user has their own private chats). Messages are role-tagged
 * (user / assistant / system) and timestamped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id');
            $table->string('role_when_created', 32); // active_role at start of chat
            $table->string('title', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_user_id', 'created_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('role', 16); // user | assistant | system
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('conversation_id')
                ->references('id')->on('ai_conversations')
                ->cascadeOnDelete();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
