<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chart of Accounts head.
 */
class AccountHead extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const TYPES = [
        'asset'     => 'Asset',
        'liability' => 'Liability',
        'income'    => 'Income',
        'expense'   => 'Expense',
        'equity'    => 'Equity',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'is_active',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
