<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_approver_level_to_approval_requests_table (CENTRAL DB)
 *
 * Adds the `approver_level` column to the existing approval_requests table.
 * This distinguishes actions that need Institute Head approval from
 * those that escalate directly to the SaaS Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            /**
             * 'institute_head' — Approver is the Institute Head (default)
             * 'saas_admin'     — Escalated; only SaaS Super Admin can approve
             *                    (e.g. InstituteHeadAssignment)
             */
            $table->string('approver_level')
                  ->default('institute_head')
                  ->after('action_type');

            $table->index(['approver_level', 'status'], 'approval_requests_approver_level_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropIndex('approval_requests_approver_level_status_index');
            $table->dropColumn('approver_level');
        });
    }
};
