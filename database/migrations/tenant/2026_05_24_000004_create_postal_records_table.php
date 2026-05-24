<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postal register — single table for both incoming (receive) and
 * outgoing (dispatch) mail/parcels, discriminated by `direction`.
 * One table → two Filament resources (Postal Receive / Postal Dispatch).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('direction', 10)->index(); // receive|dispatch
            $table->string('reference_no')->nullable();
            $table->string('from_party')->nullable();
            $table->string('to_party')->nullable();
            $table->string('title'); // subject
            $table->text('details')->nullable();
            $table->ulid('postal_type_id')->nullable(); // front_office_references (postal_type)
            $table->date('record_date');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->text('note')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('postal_type_id')->references('id')->on('front_office_references')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['direction', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_records');
    }
};
