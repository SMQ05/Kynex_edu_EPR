<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\ExamResult
 */
class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'exam_id'             => $this->exam_id,
            'exam_name'           => $this->whenLoaded('exam', fn () => $this->exam->name),
            'student_id'          => $this->student_id,
            'student_name'        => $this->whenLoaded('student', fn () => $this->student->first_name . ' ' . $this->student->last_name),
            'class_id'            => $this->class_id,
            'class_name'          => $this->whenLoaded('schoolClass', fn () => $this->schoolClass->name),
            'total_marks'         => $this->total_marks,
            'obtained_marks'      => $this->obtained_marks,
            'percentage'          => $this->percentage,
            'grade'               => $this->grade,
            'gpa'                 => $this->gpa,
            'rank'                => $this->rank,
            'status'              => $this->status?->value,
            'remarks'             => $this->remarks,
            'total_subjects'      => $this->total_subjects,
            'subjects_passed'     => $this->subjects_passed,
            'subjects_failed'     => $this->subjects_failed,
            'is_published'        => $this->is_published,
            'published_at'        => $this->published_at?->toIso8601String(),
            'created_at'          => $this->created_at->toIso8601String(),
        ];
    }
}
