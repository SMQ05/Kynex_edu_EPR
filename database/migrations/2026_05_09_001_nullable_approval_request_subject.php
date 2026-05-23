<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make subject_type / subject_id nullable on approval_requests.
 *
 * ApprovalService::submit() accepts ?Model $subject and uses the
 * payload to carry the target reference when the subject row lives
 * in a tenant DB (e.g. multi-stage admission_decision_override).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('approval_requests', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->change();
            $table->string('subject_id')->nullable()->change();
        });
    }

    public function getConnection(): string { return 'central'; }

    public function down(): void
    {
        Schema::connection('central')->table('approval_requests', function (Blueprint $table) {
            $table->string('subject_type')->nullable(false)->change();
            $table->string('subject_id')->nullable(false)->change();
        });
    }
};
