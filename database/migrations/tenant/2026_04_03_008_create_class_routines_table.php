<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_routines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('academic_year_id');
            $table->string('day_of_week')->default('monday');
            $table->tinyInteger('period_number');
            $table->ulid('subject_id')->nullable();
            $table->ulid('teacher_id')->nullable();
            $table->string('room_number')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->string('break_label')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->unique(
                ['class_id', 'section_id', 'day_of_week', 'period_number', 'academic_year_id'],
                'routine_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_routines');
    }
};
