<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'gateway',
        'student_id',
        'fee_payment_id',
        'transaction_id',
        'gateway_reference',
        'amount_paisas',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisas'    => 'integer',
            'request_payload'  => 'array',
            'response_payload' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class);
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
