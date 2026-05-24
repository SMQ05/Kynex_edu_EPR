<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * PlatformApiSetting — Platform-level API credentials & configuration.
 *
 * Stores key-value settings grouped by service type (sms, whatsapp, ai).
 * Sensitive values are automatically encrypted/decrypted.
 *
 * @property int         $id
 * @property string      $group          sms | whatsapp | ai
 * @property string      $key
 * @property string|null $value
 * @property bool        $is_encrypted
 */
class PlatformApiSetting extends Model
{
    protected $table = 'platform_api_settings';

    /**
     * Always resolve against the central DB — platform_api_settings is a
     * central-only table. Prevents tenant-DB lookups when tenancy is active.
     */
    protected $connection = 'central';

    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    // ── Accessors / Mutators ────────────────────────────────────

    /**
     * Decrypt value on retrieval if marked as encrypted.
     */
    public function getValueAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($this->is_encrypted) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value; // Return raw if decryption fails
            }
        }

        return $value;
    }

    /**
     * Encrypt value on storage if marked as encrypted.
     */
    public function setValueAttribute(?string $value): void
    {
        if ($value !== null && $value !== '' && $this->is_encrypted) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    // ── Static Helpers ──────────────────────────────────────────

    /**
     * Get a setting value by group and key.
     */
    public static function get(string $group, string $key, ?string $default = null): ?string
    {
        $setting = static::where('group', $group)->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set a setting value by group and key.
     */
    public static function set(string $group, string $key, ?string $value, bool $encrypted = false): static
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => null, 'is_encrypted' => $encrypted], // Set value null first
        );

        // Now set value with encryption awareness
        $setting->is_encrypted = $encrypted;
        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    /**
     * Get all settings for a group as an associative array.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Check if a group is enabled (has an 'enabled' key set to truthy value).
     */
    public static function isEnabled(string $group): bool
    {
        $val = static::get($group, 'enabled', '0');

        return in_array($val, ['1', 'true', 'yes'], true);
    }

    // ── Predefined Setting Schemas ─────────────────────────────

    /**
     * Define the expected settings for each API group.
     * Used by the settings page to know which fields to render.
     */
    public static function schema(): array
    {
        return [
            'sms' => [
                'label'       => 'SMS — Android SMS Gateway',
                'description' => 'Android SMS Gateway by capcom6. Send SMS via your Android phone as a gateway.',
                'docs_url'    => 'https://github.com/capcom6/android-sms-gateway',
                'icon'        => 'heroicon-o-device-phone-mobile',
                'fields'      => [
                    'enabled'    => ['type' => 'toggle', 'label' => 'Enable SMS Gateway', 'encrypted' => false],
                    'api_url'    => ['type' => 'url', 'label' => 'Gateway API URL', 'placeholder' => 'https://sms.example.com', 'encrypted' => false],
                    'login'      => ['type' => 'text', 'label' => 'Login / Username', 'placeholder' => 'Your gateway login', 'encrypted' => false],
                    'password'   => ['type' => 'password', 'label' => 'Password', 'placeholder' => '••••••••', 'encrypted' => true],
                ],
            ],
            'whatsapp' => [
                'label'       => 'WhatsApp — Evolution API',
                'description' => 'Evolution API for WhatsApp messaging. Self-hosted WhatsApp API gateway.',
                'docs_url'    => 'https://github.com/EvolutionAPI/evolution-api',
                'icon'        => 'heroicon-o-chat-bubble-left-right',
                'fields'      => [
                    'enabled'       => ['type' => 'toggle', 'label' => 'Enable WhatsApp API', 'encrypted' => false],
                    'api_url'       => ['type' => 'url', 'label' => 'Evolution API URL', 'placeholder' => 'https://evolution.yourdomain.com', 'encrypted' => false],
                    'api_key'       => ['type' => 'password', 'label' => 'Global API Key', 'placeholder' => 'Your Evolution API key', 'encrypted' => true],
                    'instance_name' => ['type' => 'text', 'label' => 'Default Instance Name', 'placeholder' => 'kynexedu-main', 'encrypted' => false],
                    'webhook_url'   => ['type' => 'url', 'label' => 'Webhook URL (incoming)', 'placeholder' => 'https://yourdomain.com/webhook/whatsapp', 'encrypted' => false],
                ],
            ],
            'ai' => [
                'label'       => 'AI — OpenRouter',
                'description' => 'OpenRouter provides unified access to 100+ AI models (GPT-4, Claude, Gemini, etc). Required for AI features.',
                'docs_url'    => 'https://openrouter.ai/',
                'icon'        => 'heroicon-o-cpu-chip',
                'fields'      => [
                    'enabled'         => ['type' => 'toggle', 'label' => 'Enable AI Features', 'encrypted' => false],
                    'api_key'         => ['type' => 'password', 'label' => 'OpenRouter API Key', 'placeholder' => 'sk-or-v1-xxxxxxxxxxxx', 'encrypted' => true],
                    'default_model'   => ['type' => 'select', 'label' => 'Default AI Model', 'encrypted' => false, 'options' => [
                        'openai/gpt-4o-mini'        => 'GPT-4o Mini (Fast & Cheap)',
                        'openai/gpt-4o'             => 'GPT-4o (Balanced)',
                        'openai/gpt-4.1'            => 'GPT-4.1 (Latest)',
                        'anthropic/claude-sonnet-4'  => 'Claude Sonnet 4',
                        'anthropic/claude-haiku'     => 'Claude Haiku (Fast)',
                        'google/gemini-2.5-flash'   => 'Gemini 2.5 Flash',
                        'google/gemini-2.5-pro'     => 'Gemini 2.5 Pro',
                        'meta-llama/llama-4-scout'  => 'Llama 4 Scout (Free)',
                        'deepseek/deepseek-chat-v3' => 'DeepSeek V3 (Budget)',
                    ]],
                    'site_url'        => ['type' => 'url', 'label' => 'Site URL (for OpenRouter ranking)', 'placeholder' => 'https://kynexedu.com', 'encrypted' => false],
                    'site_name'       => ['type' => 'text', 'label' => 'Site Name', 'placeholder' => 'KynexEdu ERP', 'encrypted' => false],
                    'max_tokens'      => ['type' => 'number', 'label' => 'Default Max Tokens', 'placeholder' => '4096', 'encrypted' => false],
                    'monthly_budget'  => ['type' => 'number', 'label' => 'Monthly Budget Limit (USD)', 'placeholder' => '50', 'encrypted' => false],
                ],
            ],
        ];
    }
}
