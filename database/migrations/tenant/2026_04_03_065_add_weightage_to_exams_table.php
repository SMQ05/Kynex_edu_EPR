<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_weightage_to_exams_table
 *
 * Part 5a — Adds weightage columns to the exams table so that
 * a weighted final score can be calculated across multiple exam types
 * (e.g. Term 1 = 30%, Term 2 = 30%, Final = 40%).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Weight of this exam in the overall annual result (0–100)
            $table->unsignedTinyInteger('weightage_percent')
                  ->default(100)
                  ->after('publish_results')
                  ->comment('Percentage weight of this exam in the annual final result (0-100)');

            // Human-readable label for this exam's contribution
            $table->string('weightage_label')
                  ->nullable()
                  ->after('weightage_percent')
                  ->comment('e.g. "First Term (30%)" — displayed on report cards');

            // Flag: should this exam contribute to the annual result?
            $table->boolean('include_in_annual_result')
                  ->default(true)
                  ->after('weightage_label');
        });

        // Also add a weight column to exam_schedules for per-subject weighting
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('theory_weight')
                  ->default(70)
                  ->after('pass_marks')
                  ->comment('% of full_marks allocated to theory component');

            $table->unsignedTinyInteger('practical_weight')
                  ->default(30)
                  ->after('theory_weight')
                  ->comment('% of full_marks allocated to practical component');

            $table->unsignedInteger('practical_full_marks')
                  ->nullable()
                  ->after('practical_weight')
                  ->comment('Full marks for practical component (if separate)');

            $table->unsignedInteger('practical_pass_marks')
                  ->nullable()
                  ->after('practical_full_marks')
                  ->comment('Pass marks for practical component');
        });

        // Add practical_marks to exam_marks
        Schema::table('exam_marks', function (Blueprint $table) {
            $table->decimal('practical_marks_obtained', 6, 2)
                  ->nullable()
                  ->after('marks_obtained')
                  ->comment('Marks obtained in practical component');
        });

        // Add weighted_percentage to exam_results
        Schema::table('exam_results', function (Blueprint $table) {
            $table->decimal('weighted_percentage', 6, 2)
                  ->nullable()
                  ->after('percentage')
                  ->comment('Percentage after applying exam weightage');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn('weighted_percentage');
        });

        Schema::table('exam_marks', function (Blueprint $table) {
            $table->dropColumn('practical_marks_obtained');
        });

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropColumn(['theory_weight', 'practical_weight', 'practical_full_marks', 'practical_pass_marks']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['weightage_percent', 'weightage_label', 'include_in_annual_result']);
        });
    }
};
