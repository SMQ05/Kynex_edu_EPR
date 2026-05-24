<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_seat_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('exam_id');
            $table->ulid('class_id')->nullable();
            $table->ulid('section_id')->nullable();
            $table->ulid('student_id');
            $table->string('room', 100);
            $table->string('seat_number', 50)->nullable();
            $table->unsignedInteger('row_number')->nullable();
            $table->unsignedInteger('column_number')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exam_id', 'student_id'], 'exam_seat_plan_student_unique');
            $table->index(['exam_id', 'room']);

            $table->foreign('exam_id')
                ->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('class_id')
                ->references('id')->on('classes')->nullOnDelete();
            $table->foreign('section_id')
                ->references('id')->on('sections')->nullOnDelete();
            $table->foreign('student_id')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_seat_plans');
    }
};
