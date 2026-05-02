<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('component_type')->default('allowance');
            $table->string('calculation_type')->default('fixed');
            $table->unsignedBigInteger('default_value_paisas')->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
