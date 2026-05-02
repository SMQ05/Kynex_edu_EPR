<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `exam_type` so an academic year can hold parallel Quarterly /
 * Mid-Term / Semi-Final / Final exams and the result aggregator can
 * weight them appropriately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_type', 32)->nullable()->after('description');
            $table->index('exam_type');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['exam_type']);
            $table->dropColumn('exam_type');
        });
    }
};
