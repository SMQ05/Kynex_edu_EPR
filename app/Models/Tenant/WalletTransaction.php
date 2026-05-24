<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUlids;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount_paisas',
        'balance_after_paisas',
        'source',
        'reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas'        => 'integer',
            'balance_after_paisas' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }
}
