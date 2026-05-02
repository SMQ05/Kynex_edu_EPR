<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\HasPaisaAttributes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelRoomType extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'name',
        'capacity',
        'description',
        'monthly_fee_paisas',
    ];

    protected array $paisaFields = ['monthly_fee_paisas'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'monthly_fee_paisas' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class, 'room_type_id');
    }
}
