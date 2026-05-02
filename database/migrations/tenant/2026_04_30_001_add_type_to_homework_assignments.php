<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `type` column so the homework table can also represent class
 * assignments and class tests — same teacher workflow, different label
 * and weighting in the annual-result aggregation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_assignments', function (Blueprint $table) {
            $table->string('type', 32)->default('homework')->after('teacher_id');
            $table->unsignedInteger('total_marks')->nullable()->after('type');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('homework_assignments', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'total_marks']);
        });
    }
};
