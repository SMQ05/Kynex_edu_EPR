<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->string('name');
            $table->unsignedInteger('capacity')->default(40);
            $table->ulid('class_teacher_id')->nullable();
            $table->string('room_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
