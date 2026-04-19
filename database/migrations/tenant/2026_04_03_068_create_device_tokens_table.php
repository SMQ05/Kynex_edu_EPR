<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id');
            $table->string('token', 255);
            $table->string('platform'); // android, ios, web
            $table->string('app_type'); // management, student_parent
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('school_user_id')
                ->references('id')
                ->on('school_users')
                ->cascadeOnDelete();

            $table->unique(['school_user_id', 'token']);
            $table->index('token');
            $table->index(['school_user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
