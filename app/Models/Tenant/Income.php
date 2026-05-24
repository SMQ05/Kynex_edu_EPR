<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An income entry. When linked to a bank account it credits that account's
 * running balance (and reverses on edit/delete/restore).
 */
class Income extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    protected $fillable = [
        'account_head_id',
        'bank_account_id',
        'title',
        'description',
        'amount_paisas',
        'income_date',
        'payment_method',
        'reference_number',
        'payer',
        'receipt_path',
        'recorded_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
            'income_date'   => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (Income $m) => BankAccount::adjust($m->bank_account_id, (int) $m->amount_paisas));

        static::updated(function (Income $m): void {
            // Reverse the original, then apply the new values.
            BankAccount::adjust($m->getOriginal('bank_account_id'), -(int) $m->getOriginal('amount_paisas'));
            BankAccount::adjust($m->bank_account_id, (int) $m->amount_paisas);
        });

        static::deleted(fn (Income $m) => BankAccount::adjust($m->bank_account_id, -(int) $m->amount_paisas));

        static::restored(fn (Income $m) => BankAccount::adjust($m->bank_account_id, (int) $m->amount_paisas));
    }

    public function accountHead(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'recorded_by');
    }
}
