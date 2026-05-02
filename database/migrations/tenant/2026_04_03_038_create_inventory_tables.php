<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->ulid('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Self-referencing FK must be added after the table (and its PK) exists — required by PostgreSQL
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('inventory_categories')->nullOnDelete();
        });

        Schema::create('inventory_stores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('location')->nullable();
            $table->foreignUlid('manager_id')->nullable()->constrained('school_users')->nullOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->foreignUlid('category_id')->constrained('inventory_categories');
            $table->foreignUlid('store_id')->constrained('inventory_stores');
            $table->string('unit')->default('piece');
            $table->unsignedInteger('current_quantity')->default(0);
            $table->unsignedInteger('minimum_quantity')->default(0);
            $table->unsignedBigInteger('unit_price_paisas')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('item_id')->constrained('inventory_items');
            $table->string('transaction_type')->default('receive');
            $table->integer('quantity'); // positive = in, negative = out
            $table->unsignedBigInteger('unit_price_paisas')->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignUlid('supplier_id')->nullable()->constrained('inventory_suppliers')->nullOnDelete();
            $table->foreignUlid('issued_to_user_id')->nullable()->constrained('school_users')->nullOnDelete();
            $table->string('issued_to_department')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('recorded_by')->constrained('school_users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('inventory_stores');
        Schema::dropIfExists('inventory_categories');
    }
};
