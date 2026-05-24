<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Carry-Forward (Infix "Fees Carry Forward"): carries a student's
 * unpaid balance from a previous academic year into the current one. Each
 * row records the carry, the source/target years and the created
 * StudentFee invoice. All money in *_paisas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_carry_forwards', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('from_academic_year_id')->nullable();
            $table->ulid('to_academic_year_id');
            $table->ulid('student_fee_id')->nullable(); // the created carry-forward StudentFee
            $table->unsignedBigInteger('amount_paisas');
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('from_academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('to_academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('student_fee_id')->references('id')->on('student_fees')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['student_id', 'to_academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_carry_forwards');
    }
};
