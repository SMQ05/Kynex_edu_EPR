<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The sessions table was created with foreignId('user_id') which produces bigint.
     * SaasAdmin (and SchoolUser) use ULID primary keys (strings), so we need the
     * user_id column to be varchar(255) instead.
     */
    public function up(): void
    {
        // Drop the index first, then alter the column type
        DB::statement('DROP INDEX IF EXISTS sessions_user_id_index');
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar(255) USING user_id::varchar');
        DB::statement('CREATE INDEX sessions_user_id_index ON sessions (user_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sessions_user_id_index');
        DB::statement('TRUNCATE sessions');
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE bigint USING user_id::bigint');
        DB::statement('CREATE INDEX sessions_user_id_index ON sessions (user_id)');
    }
};
