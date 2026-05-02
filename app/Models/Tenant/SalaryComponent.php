<?php

namespace App\Models\Tenant;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentType;
use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'name',
        'component_type',
        'calculation_type',
        'default_value_paisas',
        'is_taxable',
        'is_active',
    ];

    protected array $paisaFields = ['default_value_paisas'];

    protected function casts(): array
    {
        return [
            'component_type'       => SalaryComponentType::class,
            'calculation_type'     => SalaryCalculationType::class,
            'default_value_paisas' => 'integer',
            'is_taxable'           => 'boolean',
            'is_active'            => 'boolean',
        ];
    }

    /* ── Scopes ────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAllowances($query)
    {
        return $query->where('component_type', SalaryComponentType::Allowance);
    }

    public function scopeDeductions($query)
    {
        return $query->where('component_type', SalaryComponentType::Deduction);
    }
}
