<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id')->unique();
            $table->string('employee_id', 50)->unique();
            $table->ulid('department_id')->nullable();
            $table->ulid('designation_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('employment_type')->default('permanent');
            $table->text('qualification')->nullable();
            $table->tinyInteger('experience_years')->default(0);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->unsignedBigInteger('basic_salary_paisas')->default(0);
            $table->ulid('campus_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_user_id')->references('id')->on('school_users')->cascadeOnDelete();
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
