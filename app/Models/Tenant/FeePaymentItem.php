<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePaymentItem extends Model
{
    use HasUlids, HasPaisaAttributes;

    /** @var list<string> */
    protected array $paisaFields = ['amount_paisas'];

    protected $fillable = [
        'payment_id',
        'student_fee_id',
        'amount_paisas',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_paisas' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'payment_id');
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }
}
