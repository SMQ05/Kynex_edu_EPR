<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_class_quizzes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('online_class_id');
            $table->string('title');
            $table->json('questions')->nullable();
            $table->tinyInteger('duration_minutes')->default(10);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('online_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_class_quizzes');
    }
};
