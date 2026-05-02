<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create WhatsApp inbox tables.
 *
 * whatsapp_conversations — one row per unique contact phone number.
 * whatsapp_messages      — all messages (inbound + outbound) per conversation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('contact_phone', 30)->unique()->index();
            $table->string('contact_name')->nullable();
            $table->json('student_ids')->nullable();        // ULIDs of matched students
            $table->string('status', 20)->default('open'); // open | bot_handled | resolved
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('unread_count')->default(0);
            $table->ulid('assigned_to')->nullable();        // FK school_users.id
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id')->index();
            $table->string('direction', 10);               // inbound | outbound
            $table->text('body');
            $table->string('sender_phone', 30)->nullable(); // inbound: parent phone
            $table->ulid('sent_by_user_id')->nullable();    // outbound: staff user
            $table->boolean('is_bot_reply')->default(false);
            $table->string('provider_message_id')->nullable(); // Meta/Evolution message ID
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('whatsapp_conversations')
                ->cascadeOnDelete();

            $table->foreign('sent_by_user_id')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
