<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\AttendanceRecord
 */
class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'student_id'       => $this->student_id,
            'student_name'     => $this->whenLoaded('student', fn () => $this->student->first_name . ' ' . $this->student->last_name),
            'roll_number'      => $this->whenLoaded('student', fn () => $this->student->roll_number),
            'class_id'         => $this->class_id,
            'class_name'       => $this->whenLoaded('schoolClass', fn () => $this->schoolClass->name),
            'section_id'       => $this->section_id,
            'section_name'     => $this->whenLoaded('section', fn () => $this->section->name),
            'academic_year_id' => $this->academic_year_id,
            'date'             => $this->date->toDateString(),
            'status'           => $this->status->value,
            'remarks'          => $this->remarks,
            'late_minutes'     => $this->late_minutes,
            'marked_by'        => $this->marked_by,
            'marker_name'      => $this->whenLoaded('marker', fn () => $this->marker->name),
            'notified_at'      => $this->notified_at?->toIso8601String(),
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
