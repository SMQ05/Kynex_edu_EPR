<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('channel'); // sms, email, whatsapp, database
            $t->string('subject')->nullable();
            $t->text('body');
            $t->json('variables')->nullable(); // available placeholders
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('notifications_log', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('template_id')->nullable();
            $t->string('channel'); // sms, email, whatsapp, database
            $t->morphs('notifiable'); // the recipient user/entity
            $t->string('subject')->nullable();
            $t->text('body');
            $t->string('status')->default('pending'); // pending, sent, failed
            $t->text('error_message')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();

            $t->index(['channel', 'status']);
            $t->index('template_id');
        });

        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->ulid('school_user_id');
            $t->string('channel'); // sms, email, whatsapp, database
            $t->string('event_type'); // e.g. fee_reminder, leave_approved
            $t->boolean('is_enabled')->default(true);
            $t->timestamps();

            $t->unique(['school_user_id', 'channel', 'event_type']);
            $t->index('school_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications_log');
        Schema::dropIfExists('notification_templates');
    }
};
