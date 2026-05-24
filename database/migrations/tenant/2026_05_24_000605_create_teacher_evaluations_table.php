<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher Evaluation module (Infix "Teacher Evaluation"): an evaluatee staff
 * member is scored against a set of criteria by an evaluator for a period.
 * Criteria scores stored as JSON; total/average computed. AI sentiment +
 * summary of the qualitative comments runs via AiClassifier / AiInsights.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_evaluations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('staff_id');                 // evaluatee → staff_profiles
            $table->ulid('evaluator_id')->nullable(); // evaluator → school_users
            $table->ulid('academic_year_id')->nullable();
            $table->string('period')->nullable();     // e.g. "Term 1 2026", "May 2026"
            $table->date('evaluation_date')->nullable();
            $table->json('criteria_scores')->nullable(); // [{name, score, max, weight?}]
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('comments')->nullable();
            $table->string('sentiment', 20)->nullable();   // positive|neutral|negative (AI)
            $table->text('ai_summary')->nullable();        // AI narrative
            $table->string('status', 20)->default('draft'); // draft|submitted|approved
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('staff_id')->references('id')->on('staff_profiles')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluations');
    }
};
