<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();

            // Data source model key (e.g. 'Student', 'AttendanceRecord', 'StudentFee', etc.)
            $table->string('base_model', 50);

            // Selected columns as JSON array
            $table->json('selected_columns');

            // Filter definitions as JSON array of {column, operator, value}
            $table->json('filters')->nullable();

            // Sorting
            $table->string('sort_by')->nullable();
            $table->string('sort_direction', 4)->default('asc'); // asc or desc

            // Grouping
            $table->string('group_by')->nullable();

            // Scheduling
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency', 10)->nullable(); // daily, weekly, monthly
            $table->string('schedule_email')->nullable();
            $table->timestamp('last_run_at')->nullable();

            // Creator
            $table->foreignUlid('created_by')->nullable()->constrained('school_users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('base_model');
            $table->index('is_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
