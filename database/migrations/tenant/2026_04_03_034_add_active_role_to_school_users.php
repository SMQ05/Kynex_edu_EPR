<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_users', 'active_role')) {
            Schema::table('school_users', function (Blueprint $t) {
                $t->string('active_role', 50)->nullable()->after('campus_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_users', 'active_role')) {
            Schema::table('school_users', function (Blueprint $t) {
                $t->dropColumn('active_role');
            });
        }
    }
};
