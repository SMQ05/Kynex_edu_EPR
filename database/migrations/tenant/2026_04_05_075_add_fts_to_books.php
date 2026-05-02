<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add PostgreSQL full-text search (FTS) to the books table.
 * Creates a tsvector column with GIN index and auto-update trigger.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Add tsvector column
        DB::statement('ALTER TABLE books ADD COLUMN search_vector tsvector');

        // 2. Populate existing rows
        DB::statement("
            UPDATE books SET search_vector =
                to_tsvector('english',
                    COALESCE(title, '') || ' ' ||
                    COALESCE(author, '') || ' ' ||
                    COALESCE(isbn, '') || ' ' ||
                    COALESCE(publisher, '')
                )
        ");

        // 3. Create GIN index
        DB::statement('
            CREATE INDEX idx_books_fts
                ON books USING GIN(search_vector)
        ');

        // 4. Create trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION update_book_search_vector()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.search_vector := to_tsvector('english',
                    COALESCE(NEW.title, '') || ' ' ||
                    COALESCE(NEW.author, '') || ' ' ||
                    COALESCE(NEW.isbn, '') || ' ' ||
                    COALESCE(NEW.publisher, '')
                );
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement('
            CREATE TRIGGER trg_book_search_vector
                BEFORE INSERT OR UPDATE OF
                    title, author, isbn, publisher
                ON books
                FOR EACH ROW
                EXECUTE FUNCTION update_book_search_vector()
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS trg_book_search_vector ON books');
        DB::statement('DROP FUNCTION IF EXISTS update_book_search_vector()');
        DB::statement('DROP INDEX IF EXISTS idx_books_fts');
        DB::statement('ALTER TABLE books DROP COLUMN IF EXISTS search_vector');
    }
};
