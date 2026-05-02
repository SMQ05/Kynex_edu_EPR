<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('category_id');
            $table->ulid('budget_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_paisas');
            $table->date('expense_date');
            $table->string('payment_method')->default('cash');
            $table->string('reference_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->ulid('recorded_by');
            $table->ulid('approved_by')->nullable();
            $table->string('approval_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('expense_categories')->cascadeOnDelete();
            $table->index(['approval_status', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
