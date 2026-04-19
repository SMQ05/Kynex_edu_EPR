<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedCertificate extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'template_id',
        'certificate_number',
        'issued_date',
        'issued_by',
        'variables_used',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'issued_date'    => 'date',
            'variables_used' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'issued_by');
    }
}
