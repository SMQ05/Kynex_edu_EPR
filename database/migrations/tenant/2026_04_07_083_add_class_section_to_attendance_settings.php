<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_class_section_to_attendance_settings
 *
 * Extends attendance_settings to support per-class/section cutoff times
 * in addition to per-campus settings. A record with class_id + section_id
 * takes priority over a campus-only record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->ulid('class_id')->nullable()->after('campus_id');
            $table->ulid('section_id')->nullable()->after('class_id');

            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();

            // Drop old unique on campus_id alone
            $table->dropUnique(['campus_id']);

            // New composite unique: per campus + class + section
            $table->unique(['campus_id', 'class_id', 'section_id'], 'attendance_settings_campus_class_section_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropUnique('attendance_settings_campus_class_section_unique');
            $table->dropForeign(['class_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['class_id', 'section_id']);

            $table->unique('campus_id');
        });
    }
};
