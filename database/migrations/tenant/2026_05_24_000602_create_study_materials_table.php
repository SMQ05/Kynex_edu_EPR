<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Study Material / Upload Content (Infix "Upload Content" + "Other Downloads"):
 * teachers upload study materials (file OR link) targeted at a class/subject for
 * students. `category` discriminates study material vs. general downloads
 * ("Other Downloads") — one table, no near-identical twins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_materials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 30)->default('study_material'); // study_material|other_download|assignment_help|syllabus_doc
            $table->ulid('class_id')->nullable();
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->ulid('academic_year_id')->nullable();
            $table->ulid('teacher_id')->nullable();
            $table->string('source_type', 10)->default('file'); // file|link
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('file_type', 40)->nullable();
            $table->date('available_from')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('download_count')->default(0);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['category', 'is_published']);
            $table->index(['class_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_materials');
    }
};
