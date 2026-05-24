<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory Item Sell (Infix "Item Sell"): sell stock to a student or
 * staff member. Recording a sale decrements stock (via a paired negative
 * inventory_transaction) and stores the buyer + amount. All money in
 * *_paisas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sales', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('item_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_paisas')->default(0);
            $table->unsignedBigInteger('total_paisas')->default(0);
            $table->string('buyer_type', 20)->default('student'); // student|staff|other
            $table->ulid('student_id')->nullable();
            $table->ulid('staff_user_id')->nullable();
            $table->string('buyer_name')->nullable(); // free-text for "other"
            $table->date('sold_on');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->ulid('transaction_id')->nullable(); // paired stock movement
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('staff_user_id')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('transaction_id')->references('id')->on('inventory_transactions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['item_id', 'sold_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sales');
    }
};
