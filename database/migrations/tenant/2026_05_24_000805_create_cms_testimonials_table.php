<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_testimonials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('quote');
            $table->unsignedTinyInteger('rating')->nullable(); // 1..5
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort']);
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_testimonials');
    }
};
