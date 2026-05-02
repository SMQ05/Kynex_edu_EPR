<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Tenant — A school/institution on the platform.
 *
 * Extends the Stancl Tenancy base Tenant model with SaaS-specific
 * columns for subscription, communication, AI, and school metadata.
 *
 * All monetary values are stored in **paisas** (1 PKR = 100 paisas).
 *
 * @property string $id                  Stancl-generated string key
 * @property string|null $plan_id        FK → subscription_plans
 * @property TenantStatus $status
 * @property string $school_name
 * @property string $admin_name
 * @property string $admin_email
 * @property string|null $admin_whatsapp
 * @property string|null $owner_whatsapp
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property string|null $current_period_start
 * @property string|null $current_period_end
 * @property int $active_student_count
 * @property int $storage_used_mb
 * @property string $whatsapp_channel
 * @property array|null $whatsapp_config
 * @property string $sms_channel
 * @property array|null $sms_config
 * @property bool $ai_enabled
 * @property string $ai_model
 * @property string|null $ai_openrouter_api_key  Per-school API key; null = use platform key
 * @property int $ai_monthly_budget_paisas
 * @property int $ai_used_this_month_paisas
 * @property string|null $internal_notes
 * @property string|null $suspended_reason
 *
 * ────────────────────────────────────────────────────────────────────
 * COMMUNICATION CHANNEL CONFIG STRUCTURES
 * ────────────────────────────────────────────────────────────────────
 *
 * WhatsApp Channels ($whatsapp_channel → $whatsapp_config keys):
 *
 *   meta_official:
 *     - phone_number_id  : Meta Business phone number ID
 *     - access_token     : Permanent system user access token
 *     - waba_id          : WhatsApp Business Account ID
 *     - verify_token     : Webhook verification token
 *
 *   evolution:
 *     - base_url         : Evolution API server URL (e.g. https://evo.school.com)
 *     - api_key          : Global API key for Evolution instance
 *     - instance_name    : Instance name connected to the school's WhatsApp
 *
 *   twilio_whatsapp:
 *     - account_sid      : Twilio Account SID
 *     - auth_token       : Twilio Auth Token
 *     - from_number      : Twilio WhatsApp sender (whatsapp:+1234567890)
 *
 *   none: (no config needed — WhatsApp disabled)
 *
 * SMS Channels ($sms_channel → $sms_config keys):
 *
 *   twilio_sms:
 *     - account_sid      : Twilio Account SID
 *     - auth_token       : Twilio Auth Token
 *     - from_number      : Twilio phone number (+1234567890)
 *
 *   jazz_sms:
 *     - username         : Jazz SMS API username
 *     - password         : Jazz SMS API password
 *     - mask             : Sender ID / mask (e.g. school name)
 *
 *   telenor_sms:
 *     - api_key          : Telenor bulk SMS API key
 *     - sender_id        : Sender ID registered with Telenor
 *
 *   zong_sms:
 *     - username         : Zong SMS API username
 *     - password         : Zong SMS API password
 *     - sender_id        : Registered sender ID
 *
 *   android_gateway:
 *     - mode             : 'cloud' or 'private'
 *     - server_url       : For private: custom server URL;
 *                          For cloud: https://api.sms-gate.app/3rdparty/v1
 *     - login            : Gateway login/username
 *     - password         : Gateway password
 *
 *   none: (no config needed — SMS disabled)
 *
 * ────────────────────────────────────────────────────────────────────
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasPaisaAttributes;

    /**
     * Always use the central connection — never the dynamic 'tenant' connection.
     * Without this, when tenancy is active the default DB connection becomes
     * 'tenant', and any Eloquent attribute access on this model (e.g. datetime
     * casts calling getDateFormat()) would try to resolve that connection even
     * after purgeTenantConnection() has removed it.
     */
    protected $connection = 'central';

    /**
     * Default paisa column used by the `price_in_pkr` accessor.
     */
    protected string $primaryPaisaColumn = 'ai_monthly_budget_paisas';

    // ── Custom Columns (Stancl data-column awareness) ──────────
    //
    // Stancl stores unknown attributes in a JSON `data` column.
    // We register our custom columns so they're stored as real DB columns.

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'plan_id',
            'status',
            'school_name',
            'admin_name',
            'admin_email',
            'admin_whatsapp',
            'owner_whatsapp',
            'trial_ends_at',
            'current_period_start',
            'current_period_end',
            'active_student_count',
            'storage_used_mb',
            'whatsapp_channel',
            'whatsapp_config',
            'sms_channel',
            'sms_config',
            'ai_enabled',
            'ai_model',
            'ai_provider',
            'ai_openrouter_api_key',
            'ai_monthly_budget_paisas',
            'ai_used_this_month_paisas',
            'internal_notes',
            'suspended_reason',
            // Part 2a: Notification control columns
            'allow_app_notifications',
            'allow_whatsapp',
            'allow_sms',
            'allow_email',
            'teachers_can_use_own_whatsapp',
            'teachers_can_send_notifications',
            // Part 4b: Bilingual UI
            'preferred_language',
        ];
    }

    // ── Casts ──────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'status'                    => TenantStatus::class,
            // Encrypted at rest — Laravel AES-256 via APP_KEY (transparent to callers)
            'whatsapp_config'           => 'encrypted:array',
            'sms_config'                => 'encrypted:array',
            'ai_openrouter_api_key'     => 'encrypted',
            'ai_enabled'                => 'boolean',
            'trial_ends_at'             => 'datetime',
            'current_period_start'      => 'date',
            'current_period_end'        => 'date',
            'active_student_count'      => 'integer',
            'storage_used_mb'           => 'integer',
            'ai_monthly_budget_paisas'  => 'integer',
            'ai_used_this_month_paisas' => 'integer',
            'preferred_language'        => 'string',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /** The subscription plan this tenant is on. */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** Invoices generated for this tenant. */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    /** Approval requests for this tenant. */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'tenant_id');
    }

    /** AI usage logs for this tenant. */
    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'tenant_id');
    }

    // ── Money Accessors ────────────────────────────────────────

    public function getAiMonthlyBudgetInPkrAttribute(): string
    {
        return $this->getPriceInPkr('ai_monthly_budget_paisas');
    }

    public function getAiUsedThisMonthInPkrAttribute(): string
    {
        return $this->getPriceInPkr('ai_used_this_month_paisas');
    }

    // ── Helper Methods ─────────────────────────────────────────

    /** Check if the tenant is currently in trial. */
    public function onTrial(): bool
    {
        return $this->status === TenantStatus::Trial
            && $this->trial_ends_at?->isFuture();
    }

    /** Check if the tenant subscription is active. */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }
}
