<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * k. IdCardsAndCertificatesSeeder
 *
 * Issues records into generated_certificates for:
 *  - 100 active students × 1 ID card each (template_type=student id card)
 *  - 5 alumni × 1 completion certificate (template_type completion)
 *
 * Uses pre-existing id_card_templates and certificate_templates rows
 * (those are system-seeded and preserved across --fresh runs). If
 * either is missing, falls back to whatever active template exists.
 */
class IdCardsAndCertificatesSeeder extends Seeder
{
    public function __construct(
        public StaffSeeder $staff,
        public StudentsAndParentsSeeder $studentsAndParents,
    ) {}

    public function run(): void
    {
        DB::table('generated_certificates')->delete();
        $issuedBy = $this->staff->userIdByLabel['principal']
            ?? $this->staff->userIdByLabel['admin'];

        // generated_certificates.template_id has a FK only on certificate_templates.
        // Student ID cards live in id_card_templates and are generated on-demand at
        // runtime via the Filament "Generate ID Cards" page (no persisted record).
        // We only seed the 5 alumni completion certificates here.
        $certTemplate = DB::table('certificate_templates')
            ->where('is_active', true)
            ->whereIn('template_type', ['completion', 'leaving', 'custom'])
            ->orderBy('created_at')
            ->first()
            ?? DB::table('certificate_templates')
                ->where('is_active', true)
                ->orderBy('created_at')
                ->first();

        if (! $certTemplate) {
            $this->command?->warn('  ⚠ No active certificate_templates row — completion certs skipped.');
        }

        $idCardCount = 0;
        $this->command?->line(
            '  · ID cards: not pre-seeded (school_admin → Generate ID Cards page renders on demand from id_card_templates)'
        );

        $certCount = 0;
        if ($certTemplate) {
            $seq = 1;
            foreach ($this->studentsAndParents->alumniIds as $alumniId) {
                $student = DB::table('students')->where('id', $alumniId)->first();
                if (! $student) {
                    continue;
                }
                $year = (int) date('Y', strtotime((string) ($student->status_changed_at ?? '2025-06-30'))) ?: 2025;
                DB::table('generated_certificates')->insert([
                    'id' => (string) Str::ulid(),
                    'student_id' => $alumniId,
                    'template_id' => $certTemplate->id,
                    'certificate_number' => sprintf('AQM-CERT-%d-%03d', $year, $seq++),
                    'issued_date' => $year . '-06-30',
                    'issued_by' => $issuedBy,
                    'variables_used' => json_encode([
                        'school_name' => SchoolIdentitySeeder::SCHOOL_NAME,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'admission_number' => $student->admission_number,
                        'graduation_year' => $year,
                    ], JSON_UNESCAPED_UNICODE),
                    'file_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $certCount++;
            }
            $this->command?->line("  ✓ completion certificates generated ({$certCount})");
        }
    }
}
