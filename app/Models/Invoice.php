<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice — Billing record for a tenant school.
 *
 * Invoice number format: KNX-{YYYY}-{MM}-{tenant_slug}
 * Example: KNX-2026-04-al-huda-school
 *
 * All monetary values are stored in **paisas** (1 PKR = 100 paisas).
 *
 * @property string $id                     ULID primary key
 * @property string $tenant_id              FK → tenants
 * @property string $invoice_number
 * @property string $period_start
 * @property string $period_end
 * @property int    $active_student_count
 * @property int    $base_amount_paisas
 * @property int    $per_student_amount_paisas
 * @property int    $sms_usage_paisas
 * @property int    $whatsapp_usage_paisas
 * @property int    $ai_usage_paisas
 * @property int    $storage_overage_paisas
 * @property int    $discount_paisas
 * @property int    $total_paisas
 * @property InvoiceStatus $status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $sent_via_whatsapp_at
 * @property \Carbon\Carbon|null $paid_at
 */
class Invoice extends Model
{
    use HasUlids, HasPaisaAttributes, SoftDeletes;

    // ── Table & Key ────────────────────────────────────────────
    protected $table = 'invoices';

    /**
     * Always resolve against the central DB — invoices is a central-only
     * table. Prevents tenant-DB lookups when tenancy is active.
     */
    protected $connection = 'central';

    /**
     * Default paisa column used by the `price_in_pkr` accessor.
     */
    protected string $primaryPaisaColumn = 'total_paisas';

    // ── Mass Assignment ────────────────────────────────────────
    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'period_start',
        'period_end',
        'active_student_count',
        'base_amount_paisas',
        'per_student_amount_paisas',
        'sms_usage_paisas',
        'whatsapp_usage_paisas',
        'ai_usage_paisas',
        'storage_overage_paisas',
        'discount_paisas',
        'total_paisas',
        'status',
        'notes',
        'sent_via_whatsapp_at',
        'paid_at',
    ];

    // ── Casts ──────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'status'                    => InvoiceStatus::class,
            'period_start'              => 'date',
            'period_end'                => 'date',
            'sent_via_whatsapp_at'      => 'datetime',
            'paid_at'                   => 'datetime',
            'active_student_count'      => 'integer',
            'base_amount_paisas'        => 'integer',
            'per_student_amount_paisas' => 'integer',
            'sms_usage_paisas'          => 'integer',
            'whatsapp_usage_paisas'     => 'integer',
            'ai_usage_paisas'           => 'integer',
            'storage_overage_paisas'    => 'integer',
            'discount_paisas'           => 'integer',
            'total_paisas'              => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /** The tenant this invoice belongs to. */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // ── Money Accessors ────────────────────────────────────────

    public function getBaseAmountInPkrAttribute(): string
    {
        return $this->getPriceInPkr('base_amount_paisas');
    }

    public function getPerStudentAmountInPkrAttribute(): string
    {
        return $this->getPriceInPkr('per_student_amount_paisas');
    }

    public function getSmsUsageInPkrAttribute(): string
    {
        return $this->getPriceInPkr('sms_usage_paisas');
    }

    public function getWhatsappUsageInPkrAttribute(): string
    {
        return $this->getPriceInPkr('whatsapp_usage_paisas');
    }

    public function getAiUsageInPkrAttribute(): string
    {
        return $this->getPriceInPkr('ai_usage_paisas');
    }

    public function getStorageOverageInPkrAttribute(): string
    {
        return $this->getPriceInPkr('storage_overage_paisas');
    }

    public function getDiscountInPkrAttribute(): string
    {
        return $this->getPriceInPkr('discount_paisas');
    }

    public function getTotalInPkrAttribute(): string
    {
        return $this->getPriceInPkr('total_paisas');
    }
}
