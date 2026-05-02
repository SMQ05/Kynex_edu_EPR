<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Vehicles ─────────────────────────────────────────────────
        Schema::create('vehicles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_type')->default('bus'); // bus, van, car
            $table->string('make')->nullable(); // e.g. Toyota, Hino
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->unsignedInteger('seating_capacity')->default(0);
            $table->string('fuel_type')->nullable(); // diesel, petrol, cng, electric
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('driver_license')->nullable();
            $table->string('gps_device_id')->nullable();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Transport Routes ─────────────────────────────────────────
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name'); // e.g. "Route A - Gulberg to School"
            $table->text('description')->nullable();
            $table->ulid('vehicle_id')->nullable();
            $table->unsignedInteger('fare_paisas')->default(0);
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->nullOnDelete();
        });

        // ── Transport Stops ──────────────────────────────────────────
        Schema::create('transport_stops', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('route_id');
            $table->string('name');
            $table->unsignedInteger('stop_order');
            $table->time('pickup_time')->nullable();
            $table->time('drop_time')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('landmark')->nullable();
            $table->timestamps();

            $table->foreign('route_id')
                ->references('id')
                ->on('transport_routes')
                ->cascadeOnDelete();

            $table->unique(['route_id', 'stop_order']);
        });

        // ── Transport Assignments (student → route/stop) ─────────────
        Schema::create('transport_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('route_id');
            $table->ulid('stop_id')->nullable();
            $table->ulid('academic_year_id');
            $table->string('direction')->default('both');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('route_id')
                ->references('id')
                ->on('transport_routes')
                ->cascadeOnDelete();

            $table->foreign('stop_id')
                ->references('id')
                ->on('transport_stops')
                ->nullOnDelete();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->cascadeOnDelete();

            $table->unique(['student_id', 'academic_year_id', 'direction'], 'transport_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_assignments');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('vehicles');
    }
};
