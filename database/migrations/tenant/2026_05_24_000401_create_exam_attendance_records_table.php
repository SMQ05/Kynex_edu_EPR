<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attendance_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('exam_schedule_id');
            $table->ulid('student_id');
            $table->string('status', 20)->default('present'); // present | absent | late | leave
            $table->text('remarks')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exam_schedule_id', 'student_id'], 'exam_attendance_unique');
            $table->index('status');

            $table->foreign('exam_schedule_id')
                ->references('id')->on('exam_schedules')->cascadeOnDelete();
            $table->foreign('student_id')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attendance_records');
    }
};
