<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolUser;
use App\Models\Tenant\CertificateTemplate;
use App\Models\Tenant\GeneratedCertificate;
use App\Models\Tenant\IdCardTemplate;
use App\Models\Tenant\Student;
use App\Models\Tenant\StaffProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate a certificate PDF for a single student.
     */
    public function generateCertificate(
        CertificateTemplate $template,
        Student $student,
        SchoolUser $issuedBy,
        array $extraVariables = [],
    ): GeneratedCertificate {
        $certificateNumber = $this->generateCertificateNumber($template->template_type);

        $variables = $this->resolveStudentVariables($student, $extraVariables);
        $variables['certificate_number'] = $certificateNumber;
        $variables['issued_date'] = now()->format('d M Y');
        $variables['issued_date_words'] = now()->format('jS \d\a\y \o\f F, Y');

        $html = $this->renderTemplate($template->html_template, $variables);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = "certificates/{$certificateNumber}.pdf";
        Storage::disk('tenant')->put($filename, $pdf->output());

        return GeneratedCertificate::create([
            'student_id'       => $student->id,
            'template_id'      => $template->id,
            'certificate_number' => $certificateNumber,
            'issued_date'      => now()->toDateString(),
            'issued_by'        => $issuedBy->id,
            'variables_used'   => $variables,
            'file_path'        => $filename,
        ]);
    }

    /**
     * Generate certificates for multiple students in bulk.
     */
    public function generateBulkCertificates(
        CertificateTemplate $template,
        Collection $students,
        SchoolUser $issuedBy,
        array $extraVariables = [],
    ): Collection {
        $generated = collect();

        foreach ($students as $student) {
            $generated->push(
                $this->generateCertificate($template, $student, $issuedBy, $extraVariables)
            );
        }

        return $generated;
    }

    /**
     * Generate an ID card PDF for a student.
     */
    public function generateStudentIdCard(IdCardTemplate $template, Student $student): string
    {
        $variables = $this->resolveStudentVariables($student);
        $variables['photo_url'] = $student->photo_path
            ? Storage::disk('tenant')->url($student->photo_path)
            : asset('images/default-avatar.png');
        $variables['barcode'] = $student->admission_number;

        $html = $this->renderTemplate($template->html_template, $variables);

        $pdf = Pdf::loadHTML($html)
            ->setPaper([0, 0, 242.65, 153.01], 'landscape') // CR80 card size (3.375" x 2.125")
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = "id-cards/student-{$student->admission_number}-" . now()->format('Ymd') . '.pdf';
        Storage::disk('tenant')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate ID cards for a staff member.
     */
    public function generateStaffIdCard(IdCardTemplate $template, StaffProfile $staff): string
    {
        $user = $staff->schoolUser;

        $variables = [
            'full_name'    => $user->name,
            'email'        => $user->email,
            'phone'        => $user->phone ?? '',
            'department'   => $staff->department?->name ?? '',
            'designation'  => $staff->designation?->title ?? '',
            'employee_id'  => $staff->employee_id ?? '',
            'joining_date' => $staff->joining_date?->format('d M Y') ?? '',
            'photo_url'    => $staff->photo_path
                ? Storage::disk('tenant')->url($staff->photo_path)
                : asset('images/default-avatar.png'),
            'barcode'      => $staff->employee_id ?? $user->id,
        ];

        $html = $this->renderTemplate($template->html_template, $variables);

        $pdf = Pdf::loadHTML($html)
            ->setPaper([0, 0, 242.65, 153.01], 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $employeeId = $staff->employee_id ?? Str::limit($user->id, 8, '');
        $filename = "id-cards/staff-{$employeeId}-" . now()->format('Ymd') . '.pdf';
        Storage::disk('tenant')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate bulk student ID cards and return a merged PDF path.
     */
    public function generateBulkStudentIdCards(IdCardTemplate $template, Collection $students): string
    {
        $htmlPages = [];

        foreach ($students as $student) {
            $variables = $this->resolveStudentVariables($student);
            $variables['photo_url'] = $student->photo_path
                ? Storage::disk('tenant')->url($student->photo_path)
                : asset('images/default-avatar.png');
            $variables['barcode'] = $student->admission_number;

            $htmlPages[] = $this->renderTemplate($template->html_template, $variables);
        }

        $combinedHtml = '<html><body>' . implode('<div style="page-break-after: always;"></div>', $htmlPages) . '</body></html>';

        $pdf = Pdf::loadHTML($combinedHtml)
            ->setPaper([0, 0, 242.65, 153.01], 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = 'id-cards/bulk-students-' . now()->format('Ymd-His') . '.pdf';
        Storage::disk('tenant')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Resolve all template variables for a student.
     */
    protected function resolveStudentVariables(Student $student, array $extra = []): array
    {
        $student->loadMissing(['schoolClass', 'section', 'academicYear', 'guardians', 'campus']);

        $guardian = $student->guardians->first();

        $variables = [
            'student_name'     => $student->full_name,
            'first_name'       => $student->first_name,
            'last_name'        => $student->last_name,
            'admission_number' => $student->admission_number,
            'roll_number'      => $student->roll_number ?? '',
            'class_name'       => $student->schoolClass?->name ?? '',
            'section_name'     => $student->section?->name ?? '',
            'academic_year'    => $student->academicYear?->name ?? '',
            'campus_name'      => $student->campus?->name ?? '',
            'date_of_birth'    => $student->date_of_birth?->format('d M Y') ?? '',
            'gender'           => $student->gender ?? '',
            'father_name'      => $guardian?->father_name ?? '',
            'mother_name'      => $guardian?->mother_name ?? '',
            'guardian_name'    => $guardian?->name ?? '',
            'guardian_phone'   => $guardian?->phone ?? '',
            'address'          => $student->current_address ?? '',
            'blood_group'      => $student->blood_group ?? '',
            'religion'         => $student->religion ?? '',
            'nationality'      => $student->nationality ?? '',
            'school_name'      => tenant()?->school_name ?? config('app.name'),
            'current_date'     => now()->format('d M Y'),
        ];

        return array_merge($variables, $extra);
    }

    /**
     * Replace placeholders in template HTML with actual values.
     */
    protected function renderTemplate(string $html, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $html = str_replace("{{" . $key . "}}", (string) $value, $html);
            $html = str_replace("{{ " . $key . " }}", (string) $value, $html);
        }

        return $html;
    }

    /**
     * Generate a unique certificate number.
     */
    protected function generateCertificateNumber(string $type): string
    {
        $prefix = match ($type) {
            'leaving'     => 'LC',
            'character'   => 'CC',
            'completion'  => 'CP',
            'achievement' => 'AC',
            default       => 'CT',
        };

        $year = now()->format('Y');
        $serial = str_pad((string) (GeneratedCertificate::count() + 1), 5, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$serial}";
    }
}
