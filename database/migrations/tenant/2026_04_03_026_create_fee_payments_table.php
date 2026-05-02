<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->string('receipt_number', 50)->unique();
            $table->unsignedBigInteger('total_amount_paisas');
            $table->date('payment_date');
            $table->string('payment_method')->default('cash');
            $table->string('bank_reference')->nullable();
            $table->ulid('collected_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
