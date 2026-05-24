<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movement of money between two bank accounts. Debits the source and
 * credits the destination running balances (reverses on edit/delete).
 */
class FundTransfer extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes, TracksCreator;

    protected $fillable = [
        'from_bank_account_id',
        'to_bank_account_id',
        'amount_paisas',
        'transfer_date',
        'reference_number',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
            'transfer_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (FundTransfer $m): void {
            BankAccount::adjust($m->from_bank_account_id, -(int) $m->amount_paisas);
            BankAccount::adjust($m->to_bank_account_id, (int) $m->amount_paisas);
        });

        static::updated(function (FundTransfer $m): void {
            // Reverse original
            BankAccount::adjust($m->getOriginal('from_bank_account_id'), (int) $m->getOriginal('amount_paisas'));
            BankAccount::adjust($m->getOriginal('to_bank_account_id'), -(int) $m->getOriginal('amount_paisas'));
            // Apply new
            BankAccount::adjust($m->from_bank_account_id, -(int) $m->amount_paisas);
            BankAccount::adjust($m->to_bank_account_id, (int) $m->amount_paisas);
        });

        static::deleted(function (FundTransfer $m): void {
            BankAccount::adjust($m->from_bank_account_id, (int) $m->amount_paisas);
            BankAccount::adjust($m->to_bank_account_id, -(int) $m->amount_paisas);
        });

        static::restored(function (FundTransfer $m): void {
            BankAccount::adjust($m->from_bank_account_id, -(int) $m->amount_paisas);
            BankAccount::adjust($m->to_bank_account_id, (int) $m->amount_paisas);
        });
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }
}
