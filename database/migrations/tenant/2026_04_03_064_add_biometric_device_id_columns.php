<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — PART 3: ZKTeco Real Device Integration
 *
 * Adds biometric_device_id column to school_users and students tables.
 * This ID is the user-ID programmed into the ZKTeco device (1–65534).
 * It links raw punch logs (which only carry device_user_id) to our users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_users', function (Blueprint $table) {
            $table->string('biometric_device_id', 10)->nullable()->after('active_role')
                ->comment('ZKTeco device user ID (1-65534)');
            $table->index('biometric_device_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('biometric_device_id', 10)->nullable()->after('hostel_room_id')
                ->comment('ZKTeco device user ID (1-65534)');
            $table->index('biometric_device_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_users', function (Blueprint $table) {
            $table->dropIndex(['biometric_device_id']);
            $table->dropColumn('biometric_device_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['biometric_device_id']);
            $table->dropColumn('biometric_device_id');
        });
    }
};
