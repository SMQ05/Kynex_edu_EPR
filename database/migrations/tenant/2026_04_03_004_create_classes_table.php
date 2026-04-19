<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->tinyInteger('numeric_level')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
