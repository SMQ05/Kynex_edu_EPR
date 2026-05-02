<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('fee_group_id');
            $table->string('name');
            $table->boolean('is_recurring')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('fee_group_id')->references('id')->on('fee_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
