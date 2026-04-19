<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Notice
 */
class NoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'content'      => $this->content,
            'target_roles' => $this->target_roles,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at'   => $this->expires_at?->toIso8601String(),
            'is_expired'   => $this->is_expired,
            'created_by'   => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator->name),
            'created_at'   => $this->created_at->toIso8601String(),
            'updated_at'   => $this->updated_at->toIso8601String(),
        ];
    }
}
