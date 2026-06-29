<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GuardianType;
use App\Enums\StudentStatus;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentCategory;
use App\Models\Tenant\StudentGuardian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Imports students from a CSV file. Resolves foreign keys by name
 * (class name, section name, campus name, year name, category name)
 * and creates the Student row + 1 or 2 guardian rows in a single
 * transaction per CSV row, so a partial save never leaves a student
 * without a guardian.
 *
 * Design goals:
 *  - Idempotent on `admission_number` — re-running a CSV updates rows
 *    instead of creating duplicates.
 *  - Validate every row first; only commit valid rows; report errors.
 *  - Lookup tables cached for the duration of one import to avoid
 *    N×lookups for class/section/year names.
 */
class StudentBulkImporter
{
    /** @var array<string,string|null> name→id lookup caches */
    private array $classes = [];
    /** @var array<string,array<int,string>> lowercase name→[id,…] for unambiguous name-only fallback */
    private array $classesByName = [];
    private array $sections = [];
    private array $years = [];
    private array $campuses = [];
    private array $categories = [];

    /**
     * @return array{
     *   created:int, updated:int, skipped:int,
     *   errors:array<int,array{row:int,error:string,data:array<string,mixed>}>
     * }
     */
    public function import(string $csvPath): array
    {
        $this->buildLookups();

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            $result['errors'][] = ['row' => 0, 'error' => 'Could not open CSV file', 'data' => []];
            return $result;
        }

        // Strip BOM if present (Excel CSVs often have one).
        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return $result;
        }
        $first = ltrim($first, "\xEF\xBB\xBF");
        rewind($handle);
        if (str_starts_with(file_get_contents($csvPath), "\xEF\xBB\xBF")) {
            // skip the BOM bytes
            fread($handle, 3);
        }

        $headerRow = fgetcsv($handle);
        if (! $headerRow) {
            $result['errors'][] = ['row' => 1, 'error' => 'CSV is empty or missing header row', 'data' => []];
            fclose($handle);
            return $result;
        }
        $header = array_map(fn ($h) => self::normaliseHeader($h), $headerRow);

        $rowIndex = 1;
        while (($cols = fgetcsv($handle)) !== false) {
            $rowIndex++;
            if (count(array_filter($cols, fn ($c) => $c !== null && $c !== '')) === 0) {
                continue; // blank line
            }
            $data = self::combineRow($header, $cols);
            try {
                $verdict = $this->processRow($data);
                if ($verdict === 'created') $result['created']++;
                elseif ($verdict === 'updated') $result['updated']++;
                else $result['skipped']++;
            } catch (\Throwable $e) {
                $result['errors'][] = [
                    'row'   => $rowIndex,
                    'error' => $e->getMessage(),
                    'data'  => $data,
                ];
                $result['skipped']++;
            }
        }
        fclose($handle);

        return $result;
    }

    private function processRow(array $r): string
    {
        $first = trim((string) ($r['first_name'] ?? ''));
        $last  = trim((string) ($r['last_name'] ?? ''));
        if ($first === '' || $last === '') {
            throw new \RuntimeException('first_name and last_name are required.');
        }

        // Pick the first phone column with a non-empty value. Null-coalesce
        // alone is wrong here because Excel often emits empty strings, not
        // nulls, for un-filled cells.
        $guardianPhone = '';
        foreach (['guardian_phone', 'father_phone', 'mother_phone'] as $field) {
            $v = trim((string) ($r[$field] ?? ''));
            if ($v !== '') { $guardianPhone = $v; break; }
        }
        if ($guardianPhone === '') {
            throw new \RuntimeException('At least one of guardian_phone / father_phone / mother_phone is required.');
        }

        // Resolve campus first: classes are unique per campus, so the class
        // lookup needs the campus to disambiguate same-named classes.
        $campusId  = $this->resolveCampus(trim((string) ($r['campus_name'] ?? '')));
        $classId   = $this->resolveClass(trim((string) ($r['class_name'] ?? '')), $campusId);
        $sectionId = $this->resolveSection(trim((string) ($r['section_name'] ?? '')), $classId);
        $yearId    = $this->resolveYear(trim((string) ($r['academic_year'] ?? '')));
        $categoryId = ! empty($r['category_name']) ? $this->resolveCategory(trim((string) $r['category_name'])) : null;

        if (! $yearId) {
            throw new \RuntimeException('academic_year not found and no current year is set.');
        }
        // Validate campus before class: the class lookup depends on the campus,
        // so a missing campus would otherwise surface as a confusing
        // "class not found" error.
        if (! $campusId) {
            $campusInput = (string) ($r['campus_name'] ?? '');
            if ($campusInput === '' && count($this->campuses) > 1) {
                throw new \RuntimeException("campus_name is required when the school has multiple campuses (this tenant has " . count($this->campuses) . ").");
            }
            throw new \RuntimeException("campus_name '" . ($campusInput ?: '(blank)') . "' not found.");
        }
        if (! $classId) {
            throw new \RuntimeException("class_name '{$r['class_name']}' not found for the resolved campus.");
        }
        // section_id is NOT NULL on the schema. resolveSection() returns null
        // for a blank/unknown section_name, so guard here instead of letting
        // PostgreSQL throw a not-null violation on insert.
        if (! $sectionId) {
            $sectionInput = trim((string) ($r['section_name'] ?? ''));
            throw new \RuntimeException("section_name '" . ($sectionInput ?: '(blank)') . "' not found for class '{$r['class_name']}'. A section is required.");
        }

        // admission_number is NOT NULL on the schema. Auto-generate one
        // when the CSV cell is blank. Format: ADM-{4-digit year}-{4-digit seq}.
        $admissionNumber = self::trimToNull($r['admission_number'] ?? null);
        $admissionGivenInCsv = $admissionNumber !== null;
        if (! $admissionNumber) {
            // Re-import idempotency without an admission_number: try to
            // find the existing student by (first + last + class + year)
            // and reuse their existing admission_number so the upsert
            // update branch is taken.
            $existing = Student::query()
                ->where('first_name', $first)
                ->where('last_name', $last)
                ->where('class_id', $classId)
                ->where('academic_year_id', $yearId)
                ->first();
            $admissionNumber = $existing?->admission_number ?? self::nextAdmissionNumber();
        }

        $studentData = [
            'first_name'           => $first,
            'last_name'            => $last,
            'class_id'             => $classId,
            'section_id'           => $sectionId,
            'academic_year_id'     => $yearId,
            'campus_id'            => $campusId,
            'category_id'          => $categoryId,
            'admission_number'     => $admissionNumber,
            'roll_number'          => self::trimToNull($r['roll_number'] ?? null),
            'date_of_birth'        => self::parseDate($r['date_of_birth'] ?? null),
            'gender'               => self::normaliseGender($r['gender'] ?? null),
            'blood_group'          => self::trimToNull($r['blood_group'] ?? null),
            'religion'             => self::trimToNull($r['religion'] ?? null),
            'nationality'          => self::trimToNull($r['nationality'] ?? null) ?: 'Pakistani',
            'phone'                => self::trimToNull($r['student_phone'] ?? null),
            'email'                => self::trimToNull($r['student_email'] ?? null),
            'address'              => self::trimToNull($r['address'] ?? null),
            'city'                 => self::trimToNull($r['city'] ?? null),
            'previous_school'      => self::trimToNull($r['previous_school'] ?? null),
            'special_needs_notes'  => self::trimToNull($r['special_needs_notes'] ?? null),
            'medical_notes'        => self::trimToNull($r['medical_notes'] ?? null),
            'admission_date'       => self::parseDate($r['admission_date'] ?? null) ?? now()->toDateString(),
            'status'               => self::normaliseStatus($r['status'] ?? null) ?? StudentStatus::Enrolled->value,
        ];

        return DB::transaction(function () use ($studentData, $r) {
            // Idempotency: if admission_number already exists, update instead.
            $verdict = 'created';
            $student = null;
            if (! empty($studentData['admission_number'])) {
                $student = Student::where('admission_number', $studentData['admission_number'])->first();
            }

            if ($student) {
                $student->update(array_filter($studentData, fn ($v) => $v !== null));
                $verdict = 'updated';
            } else {
                $student = Student::create($studentData);
            }

            // ── Guardians ────────────────────────────────────────
            // Two strategies supported in the CSV:
            //  (a) Single guardian: guardian_name, guardian_phone,
            //      guardian_email, guardian_relationship.
            //  (b) Father / Mother explicitly: father_name, father_phone,
            //      father_email, mother_name, mother_phone, mother_email.
            $guardiansToCreate = self::buildGuardiansFromRow($r);

            // Sync — wipe existing rows and re-create when updating, so
            // re-importing the same CSV converges to the desired state
            // rather than piling up duplicates.
            if ($verdict === 'updated') {
                $student->guardians()->delete();
            }

            foreach ($guardiansToCreate as $g) {
                $g['student_id'] = $student->id;
                StudentGuardian::create($g);
            }

            return $verdict;
        });
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function buildGuardiansFromRow(array $r): array
    {
        $rows = [];

        // Father (if any field is present)
        $fName = trim((string) ($r['father_name'] ?? ''));
        if ($fName !== '') {
            $rows[] = self::guardianRow(GuardianType::Father->value, [
                'name'  => $fName,
                'phone' => $r['father_phone'] ?? null,
                'email' => $r['father_email'] ?? null,
                'whatsapp'   => $r['father_whatsapp'] ?? null,
                'occupation' => $r['father_occupation'] ?? null,
                'cnic'       => $r['father_cnic'] ?? null,
                'is_primary' => true,
            ]);
        }

        $mName = trim((string) ($r['mother_name'] ?? ''));
        if ($mName !== '') {
            $rows[] = self::guardianRow(GuardianType::Mother->value, [
                'name'  => $mName,
                'phone' => $r['mother_phone'] ?? null,
                'email' => $r['mother_email'] ?? null,
                'whatsapp'   => $r['mother_whatsapp'] ?? null,
                'occupation' => $r['mother_occupation'] ?? null,
                'cnic'       => $r['mother_cnic'] ?? null,
                'is_primary' => empty($rows),
            ]);
        }

        // Generic single-guardian fields
        $gName = trim((string) ($r['guardian_name'] ?? ''));
        if ($gName !== '' && empty($rows)) {
            $type = strtolower((string) ($r['guardian_relationship'] ?? 'guardian'));
            $valid = in_array($type, ['father', 'mother', 'guardian'], true) ? $type : 'guardian';
            $rows[] = self::guardianRow($valid, [
                'name'       => $gName,
                'phone'      => $r['guardian_phone'] ?? null,
                'email'      => $r['guardian_email'] ?? null,
                'whatsapp'   => $r['guardian_whatsapp'] ?? null,
                'occupation' => $r['guardian_occupation'] ?? null,
                'cnic'       => $r['guardian_cnic'] ?? null,
                'is_primary' => true,
            ]);
        }

        // If still nothing but we have guardian_phone, create a stub so
        // the school can edit the name later. The CSV requires a phone
        // upstream, so this is a last-resort guard.
        if (empty($rows) && ! empty($r['guardian_phone'])) {
            $rows[] = self::guardianRow('guardian', [
                'name'       => trim($r['first_name'] . ' ' . $r['last_name']) . ' Guardian',
                'phone'      => $r['guardian_phone'],
                'is_primary' => true,
            ]);
        }

        return $rows;
    }

    private static function guardianRow(string $type, array $data): array
    {
        return [
            'guardian_type'      => $type,
            'name'               => $data['name'] ?? null,
            'relationship'       => ucfirst($type),
            'phone'              => self::trimToNull($data['phone'] ?? null) ?? '',
            'email'              => self::trimToNull($data['email'] ?? null),
            'whatsapp'           => self::trimToNull($data['whatsapp'] ?? null),
            'occupation'         => self::trimToNull($data['occupation'] ?? null),
            'cnic'               => self::trimToNull($data['cnic'] ?? null),
            'is_primary_contact' => (bool) ($data['is_primary'] ?? false),
            'can_access_portal'  => true,
        ];
    }

    // ── Lookups ───────────────────────────────────────────────────────

    private function buildLookups(): void
    {
        // Classes are unique per campus, so the same name can legitimately exist
        // under two campuses. Key the primary lookup by campus to avoid resolving
        // "Grade 1 @ Campus B" to "Grade 1 @ Campus A"; keep a name→[ids] map for
        // an unambiguous name-only fallback (single-campus / legacy CSVs).
        $this->classes = [];
        $this->classesByName = [];
        foreach (SchoolClass::query()->select('id', 'name', 'campus_id')->get() as $c) {
            $nameKey = strtolower((string) $c->name);
            $this->classes[($c->campus_id ?? '') . '|' . $nameKey] = $c->id;
            $this->classesByName[$nameKey][] = $c->id;
        }
        $this->sections = Section::query()->select('id', 'name', 'class_id')->get()
            ->mapWithKeys(fn ($s) => [strtolower($s->class_id . '|' . $s->name) => $s->id])->all();
        $this->years    = AcademicYear::query()->pluck('id', 'name')->all();
        $this->campuses = Campus::query()->pluck('id', 'name')->all();
        try {
            $this->categories = StudentCategory::query()->pluck('id', 'name')->all();
        } catch (\Throwable) {
            $this->categories = [];
        }
    }

    private function resolveClass(string $name, ?string $campusId): ?string
    {
        if ($name === '') return null;
        $nameKey = strtolower($name);

        // 1. Exact match within the resolved campus.
        if ($campusId !== null && isset($this->classes[$campusId . '|' . $nameKey])) {
            return $this->classes[$campusId . '|' . $nameKey];
        }

        // 2. A class not tied to any campus (campus_id IS NULL) matches regardless.
        if (isset($this->classes['|' . $nameKey])) {
            return $this->classes['|' . $nameKey];
        }

        // 3. Name-only fallback, but only when unambiguous across campuses —
        //    otherwise we'd risk silently placing the student in the wrong campus.
        $ids = $this->classesByName[$nameKey] ?? [];
        return count($ids) === 1 ? $ids[0] : null;
    }

    private function resolveSection(string $name, ?string $classId): ?string
    {
        if ($name === '' || ! $classId) return null;
        $key = strtolower($classId . '|' . $name);
        return $this->sections[$key] ?? null;
    }

    private function resolveYear(string $name): ?string
    {
        if ($name === '') {
            $current = AcademicYear::query()->where('is_current', true)->first();
            return $current?->id;
        }
        return $this->years[$name] ?? $this->fuzzyLookup($this->years, $name);
    }

    private function resolveCampus(string $name): ?string
    {
        if ($name === '') {
            // Default to the only campus when there's just one.
            if (count($this->campuses) === 1) return array_values($this->campuses)[0];
            return null;
        }
        return $this->campuses[$name] ?? $this->fuzzyLookup($this->campuses, $name);
    }

    private function resolveCategory(string $name): ?string
    {
        return $this->categories[$name] ?? $this->fuzzyLookup($this->categories, $name);
    }

    /**
     * Case-insensitive fallback when an exact match fails.
     */
    private function fuzzyLookup(array $map, string $needle): ?string
    {
        $needleLower = strtolower(trim($needle));
        foreach ($map as $k => $v) {
            if (strtolower((string) $k) === $needleLower) {
                return (string) $v;
            }
        }
        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private static function normaliseHeader(string $h): string
    {
        $h = trim(strtolower($h));
        $h = preg_replace('/\s+/', '_', $h);
        return preg_replace('/[^a-z0-9_]/', '', $h);
    }

    /**
     * @param  array<int,string>  $headers
     * @param  array<int,?string>  $cols
     * @return array<string,?string>
     */
    private static function combineRow(array $headers, array $cols): array
    {
        $out = [];
        foreach ($headers as $i => $name) {
            $out[$name] = $cols[$i] ?? null;
        }
        return $out;
    }

    /**
     * Reserve the next admission number. Looks at all existing rows and
     * picks max(serial) + 1. Format: ADM-{4-digit year}-{4-digit seq}.
     */
    private static function nextAdmissionNumber(): string
    {
        $year = date('Y');
        $prefix = "ADM-{$year}-";

        // Find the highest existing serial under this year prefix.
        $existing = Student::query()
            ->where('admission_number', 'like', $prefix . '%')
            ->orderByDesc('admission_number')
            ->value('admission_number');

        $next = 1;
        if ($existing && preg_match('/-(\d+)$/', $existing, $m)) {
            $next = (int) $m[1] + 1;
        }

        // Also factor in raw count to avoid collisions if numbering scheme drifts.
        $byCount = Student::query()->count() + 1;
        if ($byCount > $next) $next = $byCount;

        return sprintf('%s%04d', $prefix, $next);
    }

    private static function trimToNull(mixed $v): ?string
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private static function parseDate(?string $v): ?string
    {
        $v = self::trimToNull($v);
        if (! $v) return null;
        // Try common formats
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd M Y', 'd-m-y', 'd/m/y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $v);
                if ($d) return $d->toDateString();
            } catch (\Throwable) { /* try next */ }
        }
        try { return Carbon::parse($v)->toDateString(); }
        catch (\Throwable) { return null; }
    }

    private static function normaliseGender(?string $v): ?string
    {
        $v = strtolower(self::trimToNull($v) ?? '');
        return match ($v) {
            'm', 'male'   => 'male',
            'f', 'female' => 'female',
            'o', 'other'  => 'other',
            default       => null,
        };
    }

    private static function normaliseStatus(?string $v): ?string
    {
        $v = strtolower(self::trimToNull($v) ?? '');
        return in_array($v, ['enrolled', 'pending_admission', 'pending', 'graduated', 'withdrawn'], true) ? $v : null;
    }

    /**
     * Returns the canonical CSV header row (in order). The download-template
     * action uses this so the user always gets a fresh, complete template.
     *
     * @return array<int,string>
     */
    public static function templateHeaders(): array
    {
        return [
            // ── Identity / required ────────────────────────────────
            'first_name', 'last_name',
            // ── Placement (required) ───────────────────────────────
            'class_name', 'section_name', 'academic_year', 'campus_name',
            // ── Optional placement ─────────────────────────────────
            'category_name', 'admission_number', 'roll_number',
            'admission_date', 'status',
            // ── Personal ───────────────────────────────────────────
            'date_of_birth', 'gender', 'blood_group', 'religion', 'nationality',
            'student_phone', 'student_email',
            'address', 'city', 'previous_school',
            'special_needs_notes', 'medical_notes',
            // ── Father ─────────────────────────────────────────────
            'father_name', 'father_phone', 'father_email', 'father_whatsapp',
            'father_occupation', 'father_cnic',
            // ── Mother ─────────────────────────────────────────────
            'mother_name', 'mother_phone', 'mother_email', 'mother_whatsapp',
            'mother_occupation', 'mother_cnic',
            // ── Single-guardian fallback ───────────────────────────
            'guardian_name', 'guardian_relationship', 'guardian_phone',
            'guardian_email', 'guardian_whatsapp', 'guardian_occupation', 'guardian_cnic',
        ];
    }

    /**
     * One example row showing every accepted format / option. The
     * download-template action prefixes this with the headers.
     *
     * @return array<int,string>
     */
    public static function templateExampleRow(): array
    {
        return [
            'Ahmed', 'Ali',
            'Class 1', 'A', '2025-26', 'Main Campus',
            'General', '', '', // admission_number + roll_number — leave blank to auto-assign
            now()->toDateString(), 'enrolled',
            '2018-04-22', 'male', 'O+', 'Islam', 'Pakistani',
            '', '',
            'Street 12, F-7/2, Islamabad', 'Islamabad', 'ABC Pre-school',
            '', '',
            // Father
            'Mr. Ali', '03001234567', 'father@example.com', '03001234567',
            'Engineer', '12345-1234567-1',
            // Mother
            'Mrs. Fatima', '03007654321', 'mother@example.com', '',
            'Teacher', '12345-7654321-2',
            // Single-guardian fallback (leave blank when father/mother filled)
            '', '', '', '', '', '', '',
        ];
    }
}
