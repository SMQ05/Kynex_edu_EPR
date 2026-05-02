<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_class_platforms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('platform_type')->default('zoom');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_class_platforms');
    }
};
