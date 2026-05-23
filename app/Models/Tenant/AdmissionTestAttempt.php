<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionTestAttempt extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_application_id', 'admission_test_id',
        'attempt_token',
        'temp_username', 'temp_password_hash', 'temp_creds_sent_at', 'checked_in_at',
        'started_at', 'submitted_at', 'expires_at',
        'status',
        'total_marks', 'obtained_marks', 'percentage',
        'needs_manual_grading',
        'graded_by', 'graded_at',
        'violation_count', 'violation_log', 'proctoring_cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'               => 'datetime',
            'submitted_at'             => 'datetime',
            'expires_at'               => 'datetime',
            'graded_at'                => 'datetime',
            'temp_creds_sent_at'       => 'datetime',
            'checked_in_at'            => 'datetime',
            'proctoring_cancelled_at'  => 'datetime',
            'total_marks'              => 'decimal:2',
            'obtained_marks'           => 'decimal:2',
            'percentage'               => 'decimal:2',
            'needs_manual_grading'     => 'boolean',
            'violation_log'            => 'array',
            'violation_count'          => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class, 'student_application_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(AdmissionTest::class, 'admission_test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AdmissionTestAnswer::class, 'attempt_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded', 'expired', 'cancelled'], true);
    }

    public function isProctoringCancelled(): bool
    {
        return $this->proctoring_cancelled_at !== null;
    }

    /** Generate random temp credentials and save them (does NOT send). Returns plain password. */
    public function generateTempCredentials(): string
    {
        $app = $this->application ?? $this->load('application')->application;
        $base = strtolower(str_replace(' ', '.', trim($app?->first_name ?? 'student')));
        $suffix = strtoupper(\Illuminate\Support\Str::random(4));
        $username = $base . '.' . $suffix;

        // Ensure uniqueness
        while (static::where('temp_username', $username)->exists()) {
            $username = $base . '.' . strtoupper(\Illuminate\Support\Str::random(4));
        }

        $plain = strtoupper(\Illuminate\Support\Str::random(3)) . rand(100, 999);

        $this->update([
            'temp_username'     => $username,
            'temp_password_hash' => \Illuminate\Support\Facades\Hash::make($plain),
        ]);

        return $plain;
    }

    /** Record a proctoring violation. Returns total violation count. */
    public function recordViolation(string $type): int
    {
        $log = $this->violation_log ?? [];
        $log[] = ['type' => $type, 'at' => now()->toIso8601String()];

        $count = ($this->violation_count ?? 0) + 1;

        $this->update([
            'violation_count' => $count,
            'violation_log'   => $log,
        ]);

        return $count;
    }
}
