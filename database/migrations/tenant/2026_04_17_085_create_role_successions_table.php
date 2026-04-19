<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_role_successions_table (TENANT DB)
 *
 * Stores role handover/succession requests when a staff member leaves.
 * The outgoing user's role, linked records (classes, subjects, workflows)
 * are transferred to the incoming user after Institute Head approval.
 *
 * For Institute Head succession itself, escalates to SaaS Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_successions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // ── Parties ──────────────────────────────────────────
            /** The staff member who is leaving. */
            $table->ulid('outgoing_user_id');
            $table->foreign('outgoing_user_id')
                  ->references('id')
                  ->on('school_users')
                  ->restrictOnDelete();

            /**
             * The new staff member taking over.
             * Nullable until the School Admin selects a replacement.
             */
            $table->ulid('incoming_user_id')->nullable();
            $table->foreign('incoming_user_id')
                  ->references('id')
                  ->on('school_users')
                  ->nullOnDelete();

            // ── Role being transferred ────────────────────────────
            /**
             * The Spatie role slug being transferred.
             * e.g. 'teacher', 'bursar', 'school_admin'
             */
            $table->string('role_slug');

            // ── What gets transferred ─────────────────────────────
            /**
             * JSON list of record types to reassign.
             * e.g. ["class_subjects", "homework", "attendance_records"]
             */
            $table->jsonb('transfer_records')->nullable();

            // ── Approval chain ────────────────────────────────────
            /**
             * 'institute_head' for all standard staff succession.
             * 'saas_admin' for Institute Head succession.
             */
            $table->string('approver_level')->default('institute_head');

            // ── Workflow ──────────────────────────────────────────
            $table->string('status')->default('pending');
            // Uses App\Enums\SuccessionStatus values

            $table->ulid('requested_by')->nullable(); // SchoolUser who initiated
            $table->foreign('requested_by')
                  ->references('id')
                  ->on('school_users')
                  ->nullOnDelete();

            $table->string('approved_by_type')->nullable(); // SchoolUser or SaasAdmin
            $table->string('approved_by_id')->nullable();   // Polymorphic
            $table->timestamp('approved_at')->nullable();

            $table->text('requester_notes')->nullable();
            $table->text('approver_notes')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['outgoing_user_id', 'status']);
            $table->index(['incoming_user_id', 'status']);
            $table->index(['role_slug', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_successions');
    }
};
