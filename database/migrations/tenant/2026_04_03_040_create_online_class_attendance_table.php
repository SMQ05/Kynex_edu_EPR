<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_class_attendance', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('online_class_id');
            $table->ulid('student_id');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('status')->default('absent');
            $table->timestamps();

            $table->unique(['online_class_id', 'student_id'], 'oca_class_student_unique');

            $table->index('online_class_id');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_class_attendance');
    }
};
