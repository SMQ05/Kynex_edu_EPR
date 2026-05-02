<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('fee_type_id');
            $table->ulid('academic_year_id');
            $table->date('due_date');
            $table->unsignedBigInteger('amount_paisas');
            $table->unsignedBigInteger('discount_paisas')->default(0);
            $table->unsignedBigInteger('fine_paisas')->default(0);
            $table->unsignedBigInteger('paid_paisas')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status', 'due_date'], 'student_fees_idx');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
