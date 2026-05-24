<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A named grouping of students (house, club, remedial group, team).
 */
class StudentGroup extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const TYPES = [
        'house'    => 'House',
        'club'     => 'Club / Society',
        'remedial' => 'Remedial',
        'team'     => 'Team',
        'general'  => 'General',
    ];

    protected $fillable = [
        'name',
        'type',
        'description',
        'color',
        'campus_id',
        'created_by',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_group_members', 'student_group_id', 'student_id')
            ->withTimestamps();
    }
}
