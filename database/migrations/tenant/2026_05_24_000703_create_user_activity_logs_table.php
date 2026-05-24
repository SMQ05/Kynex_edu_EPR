<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — User Log (Infix "User Log"): general user activity / login log.
 * Distinct from PiiAccessLog (PII-access only) and audit logs. Written via
 * the App\Support\UserActivity helper (call sites reported, not auto-wired).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id')->nullable();
            $table->string('action');                    // login|logout|created|updated|deleted|viewed|...
            $table->string('subject_type')->nullable();  // model class or feature key
            $table->ulid('subject_id')->nullable();
            $table->string('description')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('school_user_id')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['school_user_id', 'action']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
