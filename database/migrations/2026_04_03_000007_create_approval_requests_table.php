<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_approval_requests_table (CENTRAL DB)
 *
 * Stores approval requests across all tenants so SaaS admin can see
 * pending approvals. Individual school approvals (owner approving
 * admin actions) are routed through here with tenant_id set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // null = SaaS-level approval; set = school-level approval
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->nullOnDelete();

            // Who requested the action (SaasAdmin or SchoolUser)
            $table->string('requested_by_type');
            $table->string('requested_by_id');

            // What action is being requested
            $table->string('action_type');
            // e.g. 'delete_student','bulk_fee_waiver','suspend_teacher',
            //      'large_expense','change_fee_structure','expel_student'

            // The model being acted on (morphable)
            $table->string('subject_type');
            $table->string('subject_id');

            // Extra data for the action
            $table->json('payload')->nullable();

            // Approval workflow
            $table->string('status')->default('pending');

            // Who reviewed it
            $table->string('reviewed_by_type')->nullable();
            $table->string('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Auto-expiry
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['requested_by_type', 'requested_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
