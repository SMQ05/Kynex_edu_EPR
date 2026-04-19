<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInstallmentItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'installment_plan_id',
        'installment_number',
        'amount_paisas',
        'due_date',
        'paid_paisas',
        'status',
        'fee_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas'      => 'integer',
            'paid_paisas'        => 'integer',
            'installment_number' => 'integer',
            'due_date'           => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FeeInstallmentPlan::class, 'installment_plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    public function getBalancePaisasAttribute(): int
    {
        return $this->amount_paisas - $this->paid_paisas;
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', ['pending', 'partial']);
    }
}
