<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->date('date');
            $table->string('status'); // present, absent, late, excused
            $table->text('remarks')->nullable();
            $table->ulid('marked_by')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->cascadeOnDelete();

            $table->foreign('section_id')
                ->references('id')
                ->on('sections')
                ->nullOnDelete();

            $table->foreign('marked_by')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();

            $table->unique(['student_id', 'date'], 'student_date_unique');
            $table->index(['class_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
