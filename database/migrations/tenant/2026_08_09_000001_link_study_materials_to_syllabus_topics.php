<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hang lectures off the syllabus.
 *
 * Without this, a syllabus is a list of intentions and the lecture library is
 * a flat feed, and nothing connects the two: a student cannot see which part
 * of the course a video belongs to, and a teacher cannot see which planned
 * topics still have no material against them.
 *
 * Nullable on purpose. Material that predates the plan, or that a teacher
 * uploads ad hoc, stays valid and simply sits outside the syllabus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_materials', function (Blueprint $table) {
            $table->char('syllabus_topic_id', 26)->nullable()->after('subject_id');
            $table->index('syllabus_topic_id');
        });
    }

    public function down(): void
    {
        Schema::table('study_materials', function (Blueprint $table) {
            $table->dropIndex(['syllabus_topic_id']);
            $table->dropColumn('syllabus_topic_id');
        });
    }
};
