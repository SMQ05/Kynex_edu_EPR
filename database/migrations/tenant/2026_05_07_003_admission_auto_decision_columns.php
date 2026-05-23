<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the decision the system computed automatically so the UI can
 * detect manual overrides and route them through the dual-approval
 * flow (institute head → exam admin).
 *
 * auto_decision_status — copy of the application status at the moment
 *   AdmissionScoringService::applyAutoDecision() last ran.
 * auto_decision_at — when that decision was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->string('auto_decision_status', 32)->nullable()->after('auto_reject_reason');
            $table->dateTime('auto_decision_at')->nullable()->after('auto_decision_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->dropColumn(['auto_decision_status', 'auto_decision_at']);
        });
    }
};
