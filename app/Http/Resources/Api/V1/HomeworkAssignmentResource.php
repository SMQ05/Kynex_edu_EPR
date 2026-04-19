<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\HomeworkAssignment
 */
class HomeworkAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'class_id'         => $this->class_id,
            'class_name'       => $this->whenLoaded('schoolClass', fn () => $this->schoolClass->name),
            'section_id'       => $this->section_id,
            'section_name'     => $this->whenLoaded('section', fn () => $this->section->name),
            'subject_id'       => $this->subject_id,
            'subject_name'     => $this->whenLoaded('subject', fn () => $this->subject->name),
            'teacher_id'       => $this->teacher_id,
            'teacher_name'     => $this->whenLoaded('teacher', fn () => $this->teacher->name),
            'title'            => $this->title,
            'description'      => $this->description,
            'due_date'         => $this->due_date->toDateString(),
            'attachment_path'  => $this->attachment_path,
            'attachment_url'   => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'is_overdue'       => $this->due_date->isPast(),
            'submission_count' => $this->submission_count,
            'my_submission'    => new HomeworkSubmissionResource($this->whenLoaded('mySubmission')),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
