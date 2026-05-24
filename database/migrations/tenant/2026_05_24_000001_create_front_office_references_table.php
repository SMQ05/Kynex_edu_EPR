<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front-office reference lists (a.k.a. "Admin Setup"): the dropdown
 * sources shared by Admission Query, Complaint, Postal, Phone Call and
 * Visitor — e.g. complaint types, enquiry sources, references, postal
 * types, visit purposes. One table keyed by `type`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_office_references', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('type', 40)->index(); // complaint_type|source|reference|postal_type|visit_purpose|call_purpose
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_office_references');
    }
};
