<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\HomeworkSubmission
 */
class HomeworkSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'homework_id'     => $this->homework_id,
            'student_id'      => $this->student_id,
            'submission_text' => $this->submission_text,
            'attachment_path' => $this->attachment_path,
            'attachment_url'  => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'submitted_at'    => $this->submitted_at?->toIso8601String(),
            'grade'           => $this->grade,
            'feedback'        => $this->feedback,
            'graded_by'       => $this->graded_by,
            'graded_at'       => $this->graded_at?->toIso8601String(),
            'is_graded'       => $this->is_graded,
            'created_at'      => $this->created_at->toIso8601String(),
            'updated_at'      => $this->updated_at->toIso8601String(),
        ];
    }
}
