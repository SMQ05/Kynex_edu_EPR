<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_classes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->ulid('teacher_id');
            $table->ulid('platform_id');
            $table->text('meeting_url');
            $table->string('meeting_id')->nullable();
            $table->string('passcode')->nullable();
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('status')->default('scheduled');
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_classes');
    }
};
