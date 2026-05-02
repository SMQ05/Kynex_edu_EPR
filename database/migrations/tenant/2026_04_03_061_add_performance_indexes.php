<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Performance indexes for heavy queries
        Schema::table('students', function (Blueprint $table) {
            $table->index(['status', 'academic_year_id'], 'idx_students_status_ay');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['class_id', 'section_id', 'date'], 'idx_attendance_class_section_date');
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'due_date'], 'idx_student_fees_status_due');
        });

        Schema::table('exam_marks', function (Blueprint $table) {
            $table->index(['exam_schedule_id', 'student_id'], 'idx_exam_marks_schedule_student');
        });

        Schema::table('communication_logs', function (Blueprint $table) {
            $table->index(['created_at', 'channel', 'status'], 'idx_comms_created_channel_status');
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_status_ay');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_class_section_date');
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropIndex('idx_student_fees_status_due');
        });

        Schema::table('exam_marks', function (Blueprint $table) {
            $table->dropIndex('idx_exam_marks_schedule_student');
        });

        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropIndex('idx_comms_created_channel_status');
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
        });
    }
};
