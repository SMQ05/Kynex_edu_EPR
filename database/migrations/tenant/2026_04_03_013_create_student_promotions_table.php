<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('from_class_id');
            $table->ulid('from_section_id');
            $table->ulid('from_academic_year_id');
            $table->ulid('to_class_id');
            $table->ulid('to_section_id');
            $table->ulid('to_academic_year_id');
            $table->ulid('promoted_by');
            $table->timestamp('promoted_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
    }
};
