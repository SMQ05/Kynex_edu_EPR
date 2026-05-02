<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryMember extends Model
{
    use HasUlids;

    protected $fillable = [
        'member_type',
        'student_id',
        'school_user_id',
        'library_card_number',
        'max_books_allowed',
        'membership_start',
        'membership_end',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_books_allowed' => 'integer',
            'membership_start'  => 'date',
            'membership_end'    => 'date',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        if ($this->member_type === 'student' && $this->student) {
            return $this->student->full_name;
        }

        if ($this->member_type === 'staff' && $this->schoolUser) {
            return $this->schoolUser->name;
        }

        return $this->library_card_number;
    }

    public function getActiveIssuesCountAttribute(): int
    {
        return $this->issues()->whereIn('status', ['issued', 'overdue'])->count();
    }

    public function getCanBorrowAttribute(): bool
    {
        return $this->is_active && $this->active_issues_count < $this->max_books_allowed;
    }
}
