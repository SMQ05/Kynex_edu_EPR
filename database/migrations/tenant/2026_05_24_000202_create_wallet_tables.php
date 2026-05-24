<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wallet module (Infix "Wallet"): per-student digital wallet with a
 * transaction ledger, approval-gated deposits, and refund requests.
 * All money in *_paisas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->bigInteger('balance_paisas')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique('student_id');
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('wallet_id');
            $table->string('type', 10); // credit|debit
            $table->unsignedBigInteger('amount_paisas');
            $table->bigInteger('balance_after_paisas');
            $table->string('source', 30)->default('adjustment'); // deposit|refund|fee_payment|cafeteria|adjustment
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['wallet_id', 'created_at']);
        });

        Schema::create('wallet_deposits', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->string('method', 30)->nullable(); // cash|bank_transfer|cheque|online
            $table->string('reference')->nullable();
            $table->string('slip_path')->nullable();
            $table->string('status', 12)->default('pending'); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->ulid('requested_by')->nullable();
            $table->ulid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('wallet_refunds', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->string('method', 30)->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 12)->default('pending'); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->ulid('requested_by')->nullable();
            $table->ulid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_refunds');
        Schema::dropIfExists('wallet_deposits');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
