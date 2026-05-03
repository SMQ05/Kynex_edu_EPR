<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original student_fees table omitted the 'month' column even though
 * FeesService::generateStudentFees relies on it for idempotency checks
 * ("don't re-generate this month's invoice for this student"). Without
 * it, every roll-out either errors (with the column missing) or
 * produces duplicates (if the code is changed to skip the check).
 *
 * Also adds 'remarks' so admins can attach a note to one-time fees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fees', 'month')) {
                $table->string('month', 7)->nullable()->after('due_date');
                $table->index(['student_id', 'fee_type_id', 'academic_year_id', 'month'], 'student_fees_billing_idx');
            }

            if (! Schema::hasColumn('student_fees', 'remarks')) {
                $table->text('remarks')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            if (Schema::hasColumn('student_fees', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('student_fees', 'month')) {
                $table->dropIndex('student_fees_billing_idx');
                $table->dropColumn('month');
            }
        });
    }
};
