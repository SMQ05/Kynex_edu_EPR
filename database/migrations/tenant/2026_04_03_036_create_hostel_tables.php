<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_buildings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type')->default('boys');
            $table->text('address')->nullable();
            $table->tinyInteger('total_floors')->default(1);
            $table->ulid('warden_id')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('campus_id');
            $table->index('warden_id');
        });

        Schema::create('hostel_room_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->tinyInteger('capacity')->default(1);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('monthly_fee_paisas')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('building_id');
            $table->ulid('room_type_id');
            $table->string('room_number', 20);
            $table->tinyInteger('floor_number')->default(0);
            $table->tinyInteger('total_beds')->default(1);
            $table->tinyInteger('occupied_beds')->default(0);
            $table->string('status')->default('available');
            $table->json('facilities')->nullable();
            $table->unsignedBigInteger('monthly_fee_paisas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['building_id', 'room_number']);
            $table->index('room_type_id');
            $table->index('status');
        });

        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('room_id');
            $table->tinyInteger('bed_number')->nullable();
            $table->ulid('allocated_by');
            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();
            $table->date('expected_check_out')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('monthly_fee_paisas');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('room_id');
            $table->index('status');
        });

        Schema::create('hostel_gate_passes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->text('purpose');
            $table->timestamp('out_date_time');
            $table->timestamp('expected_return_date_time')->nullable();
            $table->timestamp('actual_return_date_time')->nullable();
            $table->ulid('approved_by')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('parent_notified_at')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_gate_passes');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostel_room_types');
        Schema::dropIfExists('hostel_buildings');
    }
};
