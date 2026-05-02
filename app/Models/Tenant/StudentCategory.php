<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentCategory extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    /* ── Relations ─────────────────────────── */

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'category_id');
    }
}
