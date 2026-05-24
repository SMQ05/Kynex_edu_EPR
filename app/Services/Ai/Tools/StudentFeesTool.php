<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\StudentFeeStatus;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;

/**
 * Read tool: outstanding fee balance for a student (by admission no/name).
 */
class StudentFeesTool extends AiTool
{
    public function name(): string
    {
        return 'student_fee_balance';
    }

    public function description(): string
    {
        return 'Get a student\'s outstanding (unpaid/overdue) fee balance by admission number or name.';
    }

    public function parameters(): array
    {
        return [
            'student' => ['type' => 'string', 'description' => 'Admission number or name'],
        ];
    }

    protected function requiredKeys(): array
    {
        return ['student'];
    }

    public function requiredPermission(): ?string
    {
        return 'view_fee_payments';
    }

    public function handle(array $args): string
    {
        $q = trim((string) ($args['student'] ?? ''));
        if ($q === '') {
            return 'No student provided.';
        }

        $student = Student::query()
            ->where('admission_number', 'ilike', $q)
            ->orWhere('first_name', 'ilike', "%{$q}%")
            ->orWhere('last_name', 'ilike', "%{$q}%")
            ->first();

        if (! $student) {
            return "No student found matching \"{$q}\".";
        }

        $fees = StudentFee::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [StudentFeeStatus::Pending, StudentFeeStatus::Partial])
            ->get();

        $outstanding = (int) $fees->sum(fn ($f) => max(0, (int) $f->balance_paisas));
        $overdue = $fees->filter(fn ($f) => $f->due_date && $f->due_date->isPast())->count();

        return sprintf(
            '%s %s: outstanding PKR %s across %d unpaid item(s), %d overdue.',
            $student->first_name,
            $student->last_name,
            number_format($outstanding / 100, 2),
            $fees->count(),
            $overdue,
        );
    }
}
