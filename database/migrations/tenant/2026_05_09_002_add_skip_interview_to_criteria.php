<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_criteria', function (Blueprint $table) {
            // When ON, interviews are skipped for all applicants under
            // this criteria. Weightages are renormalised across the
            // remaining configured components (test + previous-school).
            $table->boolean('skip_interview_for_all')->default(false)->after('auto_reject_below_interview');
        });
    }

    public function down(): void
    {
        Schema::table('admission_criteria', function (Blueprint $table) {
            $table->dropColumn('skip_interview_for_all');
        });
    }
};
