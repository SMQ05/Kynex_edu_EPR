<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student aggregated annual result. Computed by AnnualResultService
 * from the weighted combination of exam_results + homework/assignment
 * scores in that academic year.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id');
            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();

            // Component sub-scores (out of 100 each)
            $table->decimal('exam_score', 6, 2)->default(0);
            $table->decimal('homework_score', 6, 2)->default(0);
            $table->decimal('class_assignment_score', 6, 2)->default(0);

            // Weights snapshot at compute time
            $table->unsignedTinyInteger('exam_weight_percent');
            $table->unsignedTinyInteger('homework_weight_percent');
            $table->unsignedTinyInteger('class_assignment_weight_percent');

            // Final aggregate
            $table->decimal('final_percentage', 6, 2)->default(0);
            $table->string('grade', 8)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->string('status', 16)->default('pass');
            $table->text('remarks')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();

            $table->unique(['academic_year_id', 'student_id'], 'annual_result_unique');
            $table->index(['class_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_results');
    }
};
