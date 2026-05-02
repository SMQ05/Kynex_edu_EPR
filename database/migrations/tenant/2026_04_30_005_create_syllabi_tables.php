<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabi', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id');
            $table->ulid('academic_year_id');
            $table->ulid('teacher_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(
                ['class_id', 'section_id', 'subject_id', 'academic_year_id'],
                'syllabus_unique',
            );
        });

        Schema::create('syllabus_topics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('syllabus_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('week_number')->nullable();
            $table->date('planned_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('status', 16)->default('planned');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('syllabus_id')->references('id')->on('syllabi')->cascadeOnDelete();
            $table->index(['syllabus_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_topics');
        Schema::dropIfExists('syllabi');
    }
};
