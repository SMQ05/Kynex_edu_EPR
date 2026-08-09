<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-lecture practice quizzes and flashcards.
 *
 * TWO DELIBERATE CHOICES
 *
 * 1. Quiz questions reuse exam_questions rather than getting their own table.
 *    A practice question and an exam question are the same thing — text, type,
 *    options, correct answer, explanation — so a second table would duplicate
 *    the model and split the question bank in half. Adding a nullable
 *    study_material_id means a question can belong to a lecture, to a question
 *    group, or to both, and a teacher can promote a practice question straight
 *    into a real exam without retyping it.
 *
 * 2. Flashcards DO get their own table. A card is front/back with no options,
 *    no marks and no correct answer, so forcing it through exam_questions would
 *    mean four permanently-null columns and a type nobody can grade.
 *
 * Practice attempts are recorded but deliberately NOT graded: the point of
 * practice is unlimited retries with immediate feedback, so the score is a
 * study aid, not an assessment. Nothing here feeds a report card.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->char('study_material_id', 26)->nullable()->after('question_group_id');
            $table->index('study_material_id');
        });

        Schema::create('lecture_flashcards', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('study_material_id', 26);
            $table->text('front');
            $table->text('back');
            // Cards are shown in a fixed teaching order rather than shuffled,
            // so a definition arrives before the idea that builds on it.
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['study_material_id', 'sort_order']);
        });

        Schema::create('lecture_quiz_attempts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('study_material_id', 26);
            $table->char('student_id', 26);
            $table->unsignedInteger('score');
            $table->unsignedInteger('total');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // A student may practise the same lecture repeatedly; every run is
            // kept so improvement over time is visible.
            $table->index(['student_id', 'study_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_quiz_attempts');
        Schema::dropIfExists('lecture_flashcards');

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropIndex(['study_material_id']);
            $table->dropColumn('study_material_id');
        });
    }
};
