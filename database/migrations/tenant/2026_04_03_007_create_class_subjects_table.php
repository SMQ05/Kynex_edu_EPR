<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id');
            $table->ulid('teacher_id')->nullable();
            $table->ulid('academic_year_id');
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(
                ['class_id', 'section_id', 'subject_id', 'academic_year_id'],
                'class_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};
