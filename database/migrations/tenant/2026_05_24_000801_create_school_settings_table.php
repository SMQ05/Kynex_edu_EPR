<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
