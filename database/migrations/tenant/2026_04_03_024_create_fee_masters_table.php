<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_masters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('fee_type_id');
            $table->ulid('academic_year_id');
            $table->unsignedBigInteger('amount_paisas');
            $table->tinyInteger('due_day')->default(10);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['class_id', 'fee_type_id', 'academic_year_id'],
                'fee_master_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_masters');
    }
};
