<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Per-student digital wallet. Balance changes go exclusively through
 * credit()/debit(), which also write the ledger entry, keeping
 * balance_paisas and wallet_transactions consistent.
 */
class Wallet extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'student_id',
        'balance_paisas',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance_paisas' => 'integer',
            'is_active'      => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    /** Get (or create) the wallet for a student. */
    public static function forStudent(string $studentId): self
    {
        return static::firstOrCreate(['student_id' => $studentId]);
    }

    /** Credit the wallet and record the ledger entry. */
    public function credit(int $amountPaisas, string $source = 'adjustment', ?string $reference = null, ?string $note = null): WalletTransaction
    {
        return $this->move('credit', $amountPaisas, $source, $reference, $note);
    }

    /** Debit the wallet (throws if insufficient balance). */
    public function debit(int $amountPaisas, string $source = 'adjustment', ?string $reference = null, ?string $note = null): WalletTransaction
    {
        if ($amountPaisas > (int) $this->balance_paisas) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        return $this->move('debit', $amountPaisas, $source, $reference, $note);
    }

    private function move(string $type, int $amountPaisas, string $source, ?string $reference, ?string $note): WalletTransaction
    {
        $amountPaisas = abs($amountPaisas);

        return DB::transaction(function () use ($type, $amountPaisas, $source, $reference, $note): WalletTransaction {
            $this->refresh();
            $newBalance = $type === 'credit'
                ? (int) $this->balance_paisas + $amountPaisas
                : (int) $this->balance_paisas - $amountPaisas;

            $this->update(['balance_paisas' => $newBalance]);

            return $this->transactions()->create([
                'type'                 => $type,
                'amount_paisas'        => $amountPaisas,
                'balance_after_paisas' => $newBalance,
                'source'               => $source,
                'reference'            => $reference,
                'note'                 => $note,
                'created_by'           => auth()->guard('school_users')->id(),
            ]);
        });
    }
}
