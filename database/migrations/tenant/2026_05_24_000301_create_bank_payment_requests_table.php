<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank-payment approval (Infix "Bank Payment"): a parent/admin submits a
 * bank-transfer fee payment with a slip; admin approves → records a
 * FeePayment against the student's outstanding fees. All money in *_paisas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_payment_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->string('bank_reference')->nullable();
            $table->date('paid_on')->nullable();
            $table->string('slip_path')->nullable();
            $table->string('status', 12)->default('pending'); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->string('receipt_number')->nullable(); // set when approved (links to FeePayment)
            $table->ulid('requested_by')->nullable();
            $table->ulid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_payment_requests');
    }
};
