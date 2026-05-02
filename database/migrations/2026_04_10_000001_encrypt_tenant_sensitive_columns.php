<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt sensitive tenant credentials at rest.
 *
 * Columns affected:
 *   - tenants.whatsapp_config  (was json → now text storing AES-256 ciphertext)
 *   - tenants.sms_config       (was json → now text storing AES-256 ciphertext)
 *   - tenants.ai_openrouter_api_key (was plain text → now AES-256 ciphertext)
 *
 * The Tenant model casts these columns as 'encrypted:array' / 'encrypted',
 * so Laravel handles encrypt/decrypt transparently via the APP_KEY.
 *
 * WHY column type change (json → text):
 *   AES-256-CBC ciphertext produced by Laravel's Crypt facade is a base64-encoded
 *   JSON string — it is not valid JSON itself. PostgreSQL json/jsonb columns reject
 *   it; text columns accept any string.
 */
return new class extends Migration
{
    // ── Helpers ────────────────────────────────────────────────────

    /**
     * Encrypt a raw JSON string (already a PHP string) and return ciphertext.
     * Used during up() to migrate existing plaintext → encrypted.
     */
    private function encryptJson(?string $json): ?string
    {
        if ($json === null || $json === '' || $json === 'null') {
            return null;
        }

        return Crypt::encryptString($json);
    }

    /**
     * Decrypt ciphertext back to a raw JSON string.
     * Used during down() to reverse the migration.
     */
    private function decryptJson(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        try {
            return Crypt::decryptString($ciphertext);
        } catch (\Throwable) {
            // Already plaintext (not encrypted) — leave as-is during rollback
            return $ciphertext;
        }
    }

    // ── Up: json → encrypted text ──────────────────────────────────

    public function up(): void
    {
        // ── 1. Change column types BEFORE re-saving data ───────────
        Schema::table('tenants', function (Blueprint $table): void {
            // Must use nullable text; existing json NULLs are preserved
            $table->text('whatsapp_config')->nullable()->change();
            $table->text('sms_config')->nullable()->change();
            // ai_openrouter_api_key is already text; no type change needed
        });

        // ── 2. Encrypt existing rows ────────────────────────────────
        DB::table('tenants')->orderBy('id')->each(function (object $row): void {
            $updates = [];

            if (! empty($row->whatsapp_config)) {
                $updates['whatsapp_config'] = $this->encryptJson($row->whatsapp_config);
            }

            if (! empty($row->sms_config)) {
                $updates['sms_config'] = $this->encryptJson($row->sms_config);
            }

            if (! empty($row->ai_openrouter_api_key)) {
                $updates['ai_openrouter_api_key'] = Crypt::encryptString($row->ai_openrouter_api_key);
            }

            if (! empty($updates)) {
                DB::table('tenants')->where('id', $row->id)->update($updates);
            }
        });
    }

    // ── Down: encrypted text → json ────────────────────────────────

    public function down(): void
    {
        // ── 1. Decrypt existing rows back to plaintext ──────────────
        DB::table('tenants')->orderBy('id')->each(function (object $row): void {
            $updates = [];

            if (! empty($row->whatsapp_config)) {
                $updates['whatsapp_config'] = $this->decryptJson($row->whatsapp_config);
            }

            if (! empty($row->sms_config)) {
                $updates['sms_config'] = $this->decryptJson($row->sms_config);
            }

            if (! empty($row->ai_openrouter_api_key)) {
                try {
                    $updates['ai_openrouter_api_key'] = Crypt::decryptString($row->ai_openrouter_api_key);
                } catch (\Throwable) {
                    // Already plaintext — no-op
                }
            }

            if (! empty($updates)) {
                DB::table('tenants')->where('id', $row->id)->update($updates);
            }
        });

        // ── 2. Restore json column types ────────────────────────────
        Schema::table('tenants', function (Blueprint $table): void {
            $table->json('whatsapp_config')->nullable()->change();
            $table->json('sms_config')->nullable()->change();
        });
    }
};
