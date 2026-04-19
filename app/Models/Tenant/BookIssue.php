<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\BookIssueStatus;
use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookIssue extends Model
{
    use HasUlids, HasPaisaAttributes;

    protected string $primaryPaisaColumn = 'fine_paisas';

    protected $fillable = [
        'book_id',
        'library_member_id',
        'issue_date',
        'due_date',
        'return_date',
        'status',
        'fine_paisas',
        'fine_paid',
        'remarks',
        'issued_by',
        'returned_to',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'  => 'date',
            'due_date'    => 'date',
            'return_date' => 'date',
            'status'      => BookIssueStatus::class,
            'fine_paisas' => 'integer',
            'fine_paid'   => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'issued_by');
    }

    public function returnedToUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'returned_to');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeCurrentlyIssued($query)
    {
        return $query->whereIn('status', [BookIssueStatus::Issued, BookIssueStatus::Overdue]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', BookIssueStatus::Issued)
            ->where('due_date', '<', now());
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === BookIssueStatus::Issued
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now());
    }
}
