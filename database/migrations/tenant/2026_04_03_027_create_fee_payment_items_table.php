<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payment_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('payment_id');
            $table->ulid('student_fee_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('fee_payments')->cascadeOnDelete();
            $table->foreign('student_fee_id')->references('id')->on('student_fees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payment_items');
    }
};
