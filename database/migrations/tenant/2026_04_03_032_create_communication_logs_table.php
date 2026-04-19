<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('channel')->default('sms');
            $table->string('recipient_phone', 20)->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('message_preview', 160)->nullable();
            $table->string('status')->default('queued');
            $table->string('provider_used')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->string('feature_context')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status', 'created_at'], 'comm_logs_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
