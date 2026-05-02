<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_payrolls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_profile_id');
            $table->ulid('school_user_id');
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->tinyInteger('working_days')->nullable();
            $table->tinyInteger('present_days')->nullable();
            $table->unsignedBigInteger('basic_salary_paisas');
            $table->unsignedBigInteger('allowances_paisas')->default(0);
            $table->unsignedBigInteger('deductions_paisas')->default(0);
            $table->unsignedBigInteger('net_salary_paisas');
            $table->string('status')->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->string('payslip_path')->nullable();
            $table->ulid('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('staff_profile_id')->references('id')->on('staff_profiles')->cascadeOnDelete();
            $table->unique(['staff_profile_id', 'month', 'year'], 'payroll_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payrolls');
    }
};
