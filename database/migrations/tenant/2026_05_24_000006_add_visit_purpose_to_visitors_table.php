<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitor enhancement (Infix parity): an optional structured "visit
 * purpose" sourced from front_office_references, alongside the existing
 * free-text `purpose`. Nullable — non-breaking for existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table): void {
            $table->ulid('visit_purpose_id')->nullable()->after('purpose');
            $table->foreign('visit_purpose_id')->references('id')->on('front_office_references')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table): void {
            $table->dropForeign(['visit_purpose_id']);
            $table->dropColumn('visit_purpose_id');
        });
    }
};
