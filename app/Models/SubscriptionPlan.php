<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SubscriptionPlan — Pricing/feature plan for tenant schools.
 *
 * All monetary values are stored in **paisas** (1 PKR = 100 paisas)
 * to avoid floating-point precision issues.
 *
 * @property string $id                      ULID primary key
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $billing_type            per_student | per_school | hybrid
 * @property int    $base_price_paisas
 * @property int    $per_student_price_paisas
 * @property int    $setup_fee_paisas
 * @property int    $max_students
 * @property int    $max_staff
 * @property int    $max_campuses
 * @property int    $storage_limit_gb
 * @property array|null $enabled_modules
 * @property int    $trial_days
 * @property bool   $is_active
 * @property bool   $is_featured
 * @property bool   $is_custom
 * @property int    $sort_order
 */
class SubscriptionPlan extends Model
{
    use HasUlids, HasPaisaAttributes, SoftDeletes;

    // ── Table & Key ────────────────────────────────────────────
    protected $table = 'subscription_plans';

    /**
     * Default paisa column used by the `price_in_pkr` accessor.
     */
    protected string $primaryPaisaColumn = 'base_price_paisas';

    // ── Mass Assignment ────────────────────────────────────────
    protected $fillable = [
        'name',
        'slug',
        'description',
        'billing_type',
        'base_price_paisas',
        'per_student_price_paisas',
        'setup_fee_paisas',
        'max_students',
        'max_staff',
        'max_campuses',
        'storage_limit_gb',
        'enabled_modules',
        'trial_days',
        'is_active',
        'is_featured',
        'is_custom',
        'api_access',
        'sort_order',
    ];

    // ── Casts ──────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'enabled_modules'          => 'array',
            'is_active'                => 'boolean',
            'is_featured'              => 'boolean',
            'is_custom'                => 'boolean',
            'api_access'               => 'boolean',
            'base_price_paisas'        => 'integer',
            'per_student_price_paisas' => 'integer',
            'setup_fee_paisas'         => 'integer',
            'max_students'             => 'integer',
            'max_staff'                => 'integer',
            'max_campuses'             => 'integer',
            'storage_limit_gb'         => 'integer',
            'trial_days'               => 'integer',
            'sort_order'               => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /** Tenants subscribed to this plan. */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    // ── Money Accessors ────────────────────────────────────────

    public function getBasePriceInPkrAttribute(): string
    {
        return $this->getPriceInPkr('base_price_paisas');
    }

    public function getPerStudentPriceInPkrAttribute(): string
    {
        return $this->getPriceInPkr('per_student_price_paisas');
    }

    public function getSetupFeeInPkrAttribute(): string
    {
        return $this->getPriceInPkr('setup_fee_paisas');
    }
}
