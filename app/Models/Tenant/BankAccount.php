<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A bank or cash account. `current_balance_paisas` is maintained by the
 * Income and FundTransfer models as entries are created/edited/deleted.
 */
class BankAccount extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'branch',
        'opening_balance_paisas',
        'current_balance_paisas',
        'is_active',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance_paisas' => 'integer',
            'current_balance_paisas' => 'integer',
            'is_active'              => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Seed the running balance from the opening balance on creation.
        static::creating(function (BankAccount $account): void {
            if ((int) $account->current_balance_paisas === 0 && (int) $account->opening_balance_paisas !== 0) {
                $account->current_balance_paisas = $account->opening_balance_paisas;
            }
        });
    }

    /** Increment (or decrement, if negative) the running balance. */
    public static function adjust(?string $id, int $deltaPaisas): void
    {
        if ($id && $deltaPaisas !== 0) {
            static::whereKey($id)->update([
                'current_balance_paisas' => \Illuminate\Support\Facades\DB::raw('current_balance_paisas + ' . (int) $deltaPaisas),
            ]);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
