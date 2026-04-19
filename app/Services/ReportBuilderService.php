<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\CustomReport;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ReportBuilderService
{
    /**
     * Available models for report building with their columns and filter keys.
     */
    public const AVAILABLE_MODELS = [
        'Student' => [
            'label'   => 'Students',
            'model'   => Student::class,
            'columns' => [
                'admission_number' => 'Admission No',
                'first_name'       => 'First Name',
                'last_name'        => 'Last Name',
                'gender'           => 'Gender',
                'date_of_birth'    => 'Date of Birth',
                'class.name'       => 'Class',
                'section.name'     => 'Section',
                'status'           => 'Status',
                'admission_date'   => 'Admission Date',
                'category.name'    => 'Category',
                'guardians.phone'  => 'Guardian Phone',
            ],
            'filters' => [
                'class_id', 'section_id', 'status',
                'gender', 'category_id', 'academic_year_id',
            ],
        ],
        'AttendanceRecord' => [
            'label'   => 'Attendance',
            'model'   => AttendanceRecord::class,
            'columns' => [
                'student.admission_number' => 'Admission No',
                'student.first_name'       => 'Student First Name',
                'student.last_name'        => 'Student Last Name',
                'date'                     => 'Date',
                'status'                   => 'Status',
                'class.name'               => 'Class',
                'section.name'             => 'Section',
                'remarks'                  => 'Remarks',
            ],
            'filters' => [
                'class_id', 'section_id', 'status',
                'date', 'student_id',
            ],
        ],
        'StudentFee' => [
            'label'   => 'Fees',
            'model'   => StudentFee::class,
            'columns' => [
                'student.admission_number' => 'Admission No',
                'student.first_name'       => 'Student First Name',
                'student.last_name'        => 'Student Last Name',
                'feeMaster.feeType.name'   => 'Fee Type',
                'total_amount_paisas'      => 'Total Amount (PKR)',
                'paid_amount_paisas'       => 'Paid Amount (PKR)',
                'due_date'                 => 'Due Date',
                'status'                   => 'Status',
            ],
            'filters' => [
                'status', 'due_date', 'student_id',
                'fee_master_id',
            ],
        ],
        'ExamResult' => [
            'label'   => 'Exam Results',
            'model'   => ExamResult::class,
            'columns' => [
                'student.admission_number' => 'Admission No',
                'student.first_name'       => 'Student First Name',
                'student.last_name'        => 'Student Last Name',
                'exam.name'                => 'Exam Name',
                'total_marks'              => 'Total Marks',
                'obtained_marks'           => 'Obtained Marks',
                'percentage'               => 'Percentage',
                'grade'                    => 'Grade',
                'rank'                     => 'Rank',
                'result_status'            => 'Result Status',
            ],
            'filters' => [
                'exam_id', 'student_id', 'result_status',
                'class_id',
            ],
        ],
        'StaffProfile' => [
            'label'   => 'Staff',
            'model'   => StaffProfile::class,
            'columns' => [
                'employee_id'              => 'Employee ID',
                'schoolUser.name'          => 'Full Name',
                'schoolUser.email'         => 'Email',
                'schoolUser.phone'         => 'Phone',
                'department.name'          => 'Department',
                'designation.name'         => 'Designation',
                'employment_type'          => 'Employment Type',
                'date_of_joining'          => 'Date of Joining',
                'basic_salary_paisas'      => 'Basic Salary (PKR)',
                'status'                   => 'Status',
            ],
            'filters' => [
                'department_id', 'designation_id',
                'employment_type', 'status',
            ],
        ],
    ];

    /**
     * Run a report and return results as a collection.
     */
    public function run(CustomReport $report): Collection
    {
        $modelConfig = self::AVAILABLE_MODELS[$report->base_model] ?? null;

        if (! $modelConfig) {
            return collect();
        }

        $modelClass = $modelConfig['model'];
        $query = $modelClass::query();

        // ── Eager-load relations from dot-notation columns ──────
        $relations = $this->extractRelations($report->selected_columns ?? []);
        if ($relations->isNotEmpty()) {
            $query->with($relations->toArray());
        }

        // ── Apply filters ───────────────────────────────────────
        $this->applyFilters($query, $report->filters ?? []);

        // ── Apply sorting ───────────────────────────────────────
        if ($report->sort_by) {
            $query->orderBy(
                $this->resolveColumnForQuery($report->sort_by),
                $report->sort_direction ?? 'asc'
            );
        }

        // ── Apply grouping ──────────────────────────────────────
        if ($report->group_by) {
            $query->groupBy($this->resolveColumnForQuery($report->group_by));
        }

        // ── Execute and map selected columns ────────────────────
        $results = $query->limit(5000)->get();

        return $results->map(function ($record) use ($report, $modelConfig) {
            $row = [];
            foreach ($report->selected_columns as $column) {
                $label = $modelConfig['columns'][$column] ?? $column;
                $value = data_get($record, $column);

                // Convert paisa columns to PKR for display
                if (Str::endsWith($column, '_paisas') && is_numeric($value)) {
                    $value = number_format($value / 100, 2);
                }

                $row[$label] = $value;
            }
            return $row;
        });
    }

    /**
     * Export a report to the given format and return the file path.
     */
    public function export(CustomReport $report, string $format = 'xlsx'): string
    {
        $results = $this->run($report);
        $filename = Str::slug($report->name) . '_' . now()->format('Ymd_His');

        return match ($format) {
            'xlsx' => $this->exportExcel($results, $filename),
            'csv'  => $this->exportCsv($results, $filename),
            'pdf'  => $this->exportPdf($results, $report, $filename),
            default => $this->exportExcel($results, $filename),
        };
    }

    /**
     * Export to Excel using spatie/simple-excel.
     */
    private function exportExcel(Collection $results, string $filename): string
    {
        $path = storage_path("app/reports/{$filename}.xlsx");
        $this->ensureReportsDirectory();

        $writer = SimpleExcelWriter::create($path);

        foreach ($results as $row) {
            $writer->addRow($row);
        }

        $writer->close();

        return $path;
    }

    /**
     * Export to CSV using spatie/simple-excel.
     */
    private function exportCsv(Collection $results, string $filename): string
    {
        $path = storage_path("app/reports/{$filename}.csv");
        $this->ensureReportsDirectory();

        $writer = SimpleExcelWriter::create($path);

        foreach ($results as $row) {
            $writer->addRow($row);
        }

        $writer->close();

        return $path;
    }

    /**
     * Export to PDF using DomPDF with a table layout.
     */
    private function exportPdf(Collection $results, CustomReport $report, string $filename): string
    {
        $path = storage_path("app/reports/{$filename}.pdf");
        $this->ensureReportsDirectory();

        $headers = $results->isNotEmpty() ? array_keys($results->first()) : [];

        $pdf = Pdf::loadView('reports.custom-report-pdf', [
            'report'  => $report,
            'headers' => $headers,
            'results' => $results,
        ])->setPaper('a4', 'landscape');

        $pdf->save($path);

        return $path;
    }

    /**
     * Extract unique relation names from dot-notation column keys.
     */
    private function extractRelations(array $columns): Collection
    {
        return collect($columns)
            ->filter(fn (string $col) => Str::contains($col, '.'))
            ->map(fn (string $col) => Str::beforeLast($col, '.'))
            ->unique()
            ->values();
    }

    /**
     * Apply filter conditions to the query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $filter) {
            $column   = $filter['column'] ?? null;
            $operator = $filter['operator'] ?? 'equals';
            $value    = $filter['value'] ?? null;

            if (! $column || $value === null || $value === '') {
                continue;
            }

            $dbColumn = $this->resolveColumnForQuery($column);

            match ($operator) {
                'equals'       => $query->where($dbColumn, $value),
                'not_equals'   => $query->where($dbColumn, '!=', $value),
                'contains'     => $query->where($dbColumn, 'LIKE', "%{$value}%"),
                'starts_with'  => $query->where($dbColumn, 'LIKE', "{$value}%"),
                'greater_than' => $query->where($dbColumn, '>', $value),
                'less_than'    => $query->where($dbColumn, '<', $value),
                'between'      => $this->applyBetweenFilter($query, $dbColumn, $value),
                default        => $query->where($dbColumn, $value),
            };
        }
    }

    /**
     * Apply a between filter (expects comma-separated or array value).
     */
    private function applyBetweenFilter(Builder $query, string $column, mixed $value): void
    {
        $range = is_array($value) ? $value : explode(',', (string) $value);
        if (count($range) === 2) {
            $query->whereBetween($column, [trim($range[0]), trim($range[1])]);
        }
    }

    /**
     * Resolve a column key to a database-compatible column name.
     * Dot-notation columns that are relations are left as-is for whereHas or orderBy.
     */
    private function resolveColumnForQuery(string $column): string
    {
        // Direct columns — no dots
        if (! Str::contains($column, '.')) {
            return $column;
        }

        // For dot notation, return the last segment (used in basic queries)
        return $column;
    }

    /**
     * Ensure the reports storage directory exists.
     */
    private function ensureReportsDirectory(): void
    {
        $dir = storage_path('app/reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Get available model options for select.
     */
    public static function getModelOptions(): array
    {
        $options = [];
        foreach (self::AVAILABLE_MODELS as $key => $config) {
            $options[$key] = $config['label'];
        }
        return $options;
    }

    /**
     * Get columns for a specific model key.
     */
    public static function getColumnsForModel(string $modelKey): array
    {
        return self::AVAILABLE_MODELS[$modelKey]['columns'] ?? [];
    }

    /**
     * Get filter keys for a specific model key.
     */
    public static function getFiltersForModel(string $modelKey): array
    {
        return self::AVAILABLE_MODELS[$modelKey]['filters'] ?? [];
    }
}
