<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'class_routines',
            'online_classes',
            'student_fees',
            'fee_masters',
            'leave_requests',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'campus_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->ulid('campus_id')->nullable()->after('id');
                    $t->index('campus_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'class_routines',
            'online_classes',
            'student_fees',
            'fee_masters',
            'leave_requests',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'campus_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['campus_id']);
                    $t->dropColumn('campus_id');
                });
            }
        }
    }
};
