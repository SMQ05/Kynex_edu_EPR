<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Chat module (Infix "Chat"): user-to-user messaging between
 * school_users. Distinct from the AI assistant (ai_conversations/ai_messages)
 * and from the WhatsApp inbox. Includes invitations and a block list.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A conversation thread (1:1 or group).
        Schema::create('conversations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title')->nullable();         // null for 1:1 (derive from participants)
            $table->string('type', 20)->default('direct'); // direct|group
            $table->ulid('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['last_message_at']);
        });

        // Participants in a conversation.
        Schema::create('conversation_participants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->ulid('school_user_id');
            $table->string('role', 20)->default('member'); // owner|member
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('school_user_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->unique(['conversation_id', 'school_user_id']);
            $table->index(['school_user_id']);
        });

        // Messages within a conversation.
        Schema::create('messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->ulid('sender_id')->nullable();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['conversation_id', 'created_at']);
        });

        // Chat invitations (request to start chatting).
        Schema::create('chat_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('inviter_id');
            $table->ulid('invitee_id');
            $table->string('status', 20)->default('pending'); // pending|accepted|declined
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('inviter_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->foreign('invitee_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->unique(['inviter_id', 'invitee_id']);
            $table->index(['invitee_id', 'status']);
        });

        // Blocked users (one school_user blocks another).
        Schema::create('chat_blocked_users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('blocker_id');
            $table->ulid('blocked_id');
            $table->string('reason')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('blocker_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->foreign('blocked_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['blocker_id', 'blocked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_blocked_users');
        Schema::dropIfExists('chat_invitations');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
