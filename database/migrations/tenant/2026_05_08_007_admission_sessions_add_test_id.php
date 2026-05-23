<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_sessions', function (Blueprint $table) {
            // Which AdmissionTest applicants in this slot will sit.
            // Nullable — interview sessions don't need a test.
            $table->ulid('test_id')->nullable()->after('type');
            $table->foreign('test_id')->references('id')->on('admission_tests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admission_sessions', function (Blueprint $table) {
            $table->dropForeign(['test_id']);
            $table->dropColumn('test_id');
        });
    }
};
