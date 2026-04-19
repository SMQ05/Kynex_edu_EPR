<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('visitor_name');
            $table->string('phone', 20)->nullable();
            $table->string('cnic', 15)->nullable();
            $table->string('organization')->nullable();
            $table->text('purpose');
            $table->string('whom_to_meet')->nullable();
            $table->foreignUlid('school_user_to_meet_id')->nullable()->constrained('school_users')->nullOnDelete();
            $table->timestamp('check_in_time')->useCurrent();
            $table->timestamp('check_out_time')->nullable();
            $table->string('badge_number', 20)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('vehicle_number', 20)->nullable();
            $table->string('status')->default('checked_in');
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignUlid('recorded_by')->constrained('school_users');
            $table->timestamps();

            $table->index(['check_in_time', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
