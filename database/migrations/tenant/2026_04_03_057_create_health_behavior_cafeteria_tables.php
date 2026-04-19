<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Health Records ──────────────────────────────────────────
        Schema::create('health_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignUlid('recorded_by')->nullable()->constrained('school_users')->nullOnDelete();

            $table->string('record_type', 30); // clinic_visit, vaccination, allergy, medical_condition, medication, check_up
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('record_date');

            // Clinic visit specifics
            $table->string('symptoms')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('treatment')->nullable();
            $table->string('medication_given')->nullable();
            $table->string('action_taken', 30)->nullable(); // sent_home, returned_to_class, referred_hospital, first_aid

            // Vaccination specifics
            $table->string('vaccine_name')->nullable();
            $table->date('next_dose_date')->nullable();

            // Allergy / Condition specifics
            $table->string('severity', 20)->nullable(); // mild, moderate, severe, life_threatening
            $table->boolean('is_chronic')->default(false);

            // Vitals (optional)
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('blood_pressure')->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();

            $table->boolean('parent_notified')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['student_id', 'record_type']);
            $table->index('record_date');
        });

        // ── Behavior Incidents ──────────────────────────────────────
        Schema::create('behavior_incidents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignUlid('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignUlid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignUlid('reported_by')->nullable()->constrained('school_users')->nullOnDelete();
            $table->foreignUlid('handled_by')->nullable()->constrained('school_users')->nullOnDelete();

            $table->string('incident_type', 30); // positive, negative, neutral
            $table->string('category', 50);       // bullying, tardiness, fighting, academic_honesty, leadership, helpfulness, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('location')->nullable();

            // Severity & impact
            $table->string('severity', 20)->default('minor'); // minor, moderate, major, critical
            $table->integer('points')->default(0); // positive = +, negative = -

            // Action taken
            $table->string('action_taken', 50)->nullable(); // verbal_warning, written_warning, detention, suspension, expulsion, reward, certificate
            $table->text('action_details')->nullable();
            $table->date('action_date')->nullable();
            $table->date('follow_up_date')->nullable();

            // Resolution
            $table->string('status', 20)->default('reported'); // reported, investigating, resolved, closed
            $table->text('resolution_notes')->nullable();

            $table->boolean('parent_notified')->default(false);
            $table->date('parent_notified_date')->nullable();
            $table->text('parent_response')->nullable();

            $table->json('witnesses')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'incident_type']);
            $table->index('incident_date');
            $table->index('status');
        });

        // ── Cafeteria Menu Items ────────────────────────────────────
        Schema::create('cafeteria_menu_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();

            $table->string('name');
            $table->string('category', 50)->default('general'); // breakfast, lunch, snack, beverage, general
            $table->text('description')->nullable();
            $table->bigInteger('price_paisas')->default(0);
            $table->string('image_path')->nullable();

            $table->boolean('is_available')->default(true);
            $table->boolean('is_vegetarian')->default(false);
            $table->string('allergens')->nullable(); // comma-separated
            $table->integer('calories')->nullable();
            $table->integer('preparation_time_minutes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['campus_id', 'category']);
        });

        // ── Cafeteria Transactions ──────────────────────────────────
        Schema::create('cafeteria_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignUlid('school_user_id')->nullable()->constrained('school_users')->nullOnDelete(); // staff buyer
            $table->foreignUlid('menu_item_id')->nullable()->constrained('cafeteria_menu_items')->nullOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignUlid('served_by')->nullable()->constrained('school_users')->nullOnDelete();

            $table->string('transaction_type', 20)->default('purchase'); // purchase, wallet_topup, wallet_refund
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_price_paisas')->default(0);
            $table->bigInteger('total_paisas')->default(0);
            $table->string('payment_method', 20)->default('cash'); // cash, wallet, card
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'transaction_date']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafeteria_transactions');
        Schema::dropIfExists('cafeteria_menu_items');
        Schema::dropIfExists('behavior_incidents');
        Schema::dropIfExists('health_records');
    }
};
