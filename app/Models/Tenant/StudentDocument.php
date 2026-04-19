<?php

namespace App\Models\Tenant;

use App\Enums\StudentDocumentType;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'document_type',
        'title',
        'file_path',
        'file_size_kb',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => StudentDocumentType::class,
            'file_size_kb'  => 'integer',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'uploaded_by');
    }
}
