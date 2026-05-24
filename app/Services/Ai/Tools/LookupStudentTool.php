<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Models\Tenant\Student;

/**
 * Read tool: find students by name or admission number and return basic
 * info. Respects model global scopes (e.g. campus). Permission-gated.
 */
class LookupStudentTool extends AiTool
{
    public function name(): string
    {
        return 'lookup_student';
    }

    public function description(): string
    {
        return 'Find students by name or admission number. Returns class, section, roll number and status.';
    }

    public function parameters(): array
    {
        return [
            'query' => ['type' => 'string', 'description' => 'Student name or admission number to search for'],
        ];
    }

    protected function requiredKeys(): array
    {
        return ['query'];
    }

    public function requiredPermission(): ?string
    {
        return 'view_students';
    }

    public function handle(array $args): string
    {
        $q = trim((string) ($args['query'] ?? ''));
        if ($q === '') {
            return 'No query provided.';
        }

        $rows = Student::query()
            ->with(['schoolClass:id,name', 'section:id,name'])
            ->where(function ($w) use ($q): void {
                $w->where('first_name', 'ilike', "%{$q}%")
                  ->orWhere('last_name', 'ilike', "%{$q}%")
                  ->orWhere('admission_number', 'ilike', "%{$q}%");
            })
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return "No students found matching \"{$q}\".";
        }

        $lines = $rows->map(function (Student $s): string {
            $status = $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status;

            return sprintf(
                '- %s %s (Adm %s) — %s %s, roll %s, status %s',
                $s->first_name,
                $s->last_name,
                $s->admission_number ?? '—',
                $s->schoolClass->name ?? '—',
                $s->section->name ?? '',
                $s->roll_number ?? '—',
                $status,
            );
        })->implode("\n");

        return "Matches:\n" . $lines;
    }
}
