<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fee Installment Plans ─────────────────────────────────
        Schema::create('fee_installment_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('academic_year_id');
            $table->string('plan_name');
            $table->unsignedBigInteger('total_amount_paisas');
            $table->unsignedSmallInteger('total_installments');
            $table->string('status')->default('active');
            $table->ulid('created_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });

        // ── Fee Installment Items ─────────────────────────────────
        Schema::create('fee_installment_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('installment_plan_id');
            $table->unsignedSmallInteger('installment_number');
            $table->unsignedBigInteger('amount_paisas');
            $table->date('due_date');
            $table->unsignedBigInteger('paid_paisas')->default(0);
            $table->string('status')->default('pending');
            $table->ulid('fee_payment_id')->nullable();
            $table->timestamps();

            $table->foreign('installment_plan_id')
                ->references('id')
                ->on('fee_installment_plans')
                ->cascadeOnDelete();

            $table->foreign('fee_payment_id')
                ->references('id')
                ->on('fee_payments')
                ->nullOnDelete();
        });

        // ── Payment Gateway Logs ──────────────────────────────────
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('gateway'); // jazzcash, easypaisa, stripe
            $table->ulid('student_id')->nullable();
            $table->ulid('fee_payment_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->unsignedBigInteger('amount_paisas');
            $table->string('status')->default('initiated');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'status']);
            $table->index('transaction_id');

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->nullOnDelete();

            $table->foreign('fee_payment_id')
                ->references('id')
                ->on('fee_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
        Schema::dropIfExists('fee_installment_items');
        Schema::dropIfExists('fee_installment_plans');
    }
};
