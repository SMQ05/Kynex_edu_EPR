<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('entity');                 // student|staff
            $table->string('label');
            $table->string('key');                    // machine key, unique per entity
            $table->string('type')->default('text');  // text|number|date|select|textarea|toggle
            $table->json('options')->nullable();      // for select
            $table->boolean('required')->default(false);
            $table->text('help_text')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['entity', 'key']);
            $table->index(['entity', 'is_active', 'sort']);
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('custom_field_id');
            $table->string('entity');                 // student|staff (denormalised for fast lookup)
            $table->ulid('entity_id');                // the student/staff record id
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'entity_id']);
            $table->index(['entity', 'entity_id']);
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
