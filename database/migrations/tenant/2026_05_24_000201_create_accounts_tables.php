<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts module (Infix "Accounts"): chart of accounts, bank accounts,
 * income entries, and fund transfers. Complements the existing Expense +
 * Budget features (no duplication). All money in *_paisas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('account_heads', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->string('type', 20)->default('income'); // asset|liability|income|expense|equity
            $table->ulid('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['type', 'is_active']);
        });

        // Self-referencing FK added after creation so the primary key exists
        // first (Postgres rejects a self-FK compiled before the PK).
        Schema::table('account_heads', function (Blueprint $table): void {
            $table->foreign('parent_id')->references('id')->on('account_heads')->nullOnDelete();
        });

        // Bank / cash accounts
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch')->nullable();
            $table->bigInteger('opening_balance_paisas')->default(0);
            $table->bigInteger('current_balance_paisas')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        // Income entries
        Schema::create('incomes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('account_head_id')->nullable();
            $table->ulid('bank_account_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_paisas');
            $table->date('income_date');
            $table->string('payment_method', 30)->nullable();
            $table->string('reference_number')->nullable();
            $table->string('payer')->nullable();
            $table->string('receipt_path')->nullable();
            $table->ulid('recorded_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_head_id')->references('id')->on('account_heads')->nullOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['income_date']);
        });

        // Fund transfers between bank accounts
        Schema::create('fund_transfers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('from_bank_account_id');
            $table->ulid('to_bank_account_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->date('transfer_date');
            $table->string('reference_number')->nullable();
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('to_bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('account_heads');
    }
};
