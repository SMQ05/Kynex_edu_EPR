<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('section_id');
            $table->ulid('subject_id');
            $table->ulid('teacher_id');
            $table->string('title');
            $table->text('description');
            $table->date('due_date');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('school_users')->cascadeOnDelete();

            $table->index(['class_id', 'section_id', 'due_date']);
        });

        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('homework_id');
            $table->ulid('student_id');
            $table->text('submission_text')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('submitted_at');
            $table->string('grade')->nullable();
            $table->text('feedback')->nullable();
            $table->ulid('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->foreign('homework_id')->references('id')->on('homework_assignments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('school_users')->nullOnDelete();

            $table->unique(['homework_id', 'student_id']);
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->json('target_roles')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->ulid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->cascadeOnDelete();

            $table->index(['is_published', 'published_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
        Schema::dropIfExists('homework_submissions');
        Schema::dropIfExists('homework_assignments');
    }
};
