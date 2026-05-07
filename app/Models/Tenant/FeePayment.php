<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Casts\AsEnum;
use App\Enums\FeePaymentMethod;
use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePayment extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    /** @var list<string> */
    protected array $paisaFields = ['total_amount_paisas'];

    protected $fillable = [
        'student_id',
        'receipt_number',
        'total_amount_paisas',
        'payment_date',
        'payment_method',
        'bank_reference',
        'collected_by',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_amount_paisas' => 'integer',
            'payment_date' => 'date',
            'payment_method' => AsEnum::of(FeePaymentMethod::class),
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'collected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeePaymentItem::class, 'payment_id');
    }
}
