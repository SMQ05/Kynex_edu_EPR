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
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CertificateService
{
    // ── Public PDF generation (in-memory, no storage) ─────────────────

    /**
     * Generate a single certificate PDF as raw bytes. Inserts a row in
     * generated_certificates so the verification page can resolve it by
     * certificate_number, but file_path stays null — bytes are streamed
     * straight to the user.
     *
     * @return array{bytes:string, filename:string, certificate:GeneratedCertificate}
     */
    public function buildCertificatePdf(
        CertificateTemplate $template,
        Student $student,
        SchoolUser $issuedBy,
        array $extraVariables = [],
    ): array {
        $certificateNumber = $this->generateCertificateNumber($template->template_type);

        $variables = $this->resolveStudentVariables($student, $extraVariables);
        $variables['certificate_number'] = $certificateNumber;
        $variables['issued_date'] = now()->format('d M Y');
        $variables['issued_date_words'] = now()->format('jS \d\a\y \o\f F, Y');
        $variables['verification_url'] = $this->verificationUrlFor($certificateNumber);
        $variables['verification_qr'] = $this->qrImageDataUri($variables['verification_url']);

        $html = $this->wrapPrintable($this->renderTemplate(
            $this->ensureVerificationFooter($template->html_template),
            $variables,
        ));

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $cert = GeneratedCertificate::create([
            'student_id'         => $student->id,
            'template_id'        => $template->id,
            'certificate_number' => $certificateNumber,
            'issued_date'        => now()->toDateString(),
            'issued_by'          => $issuedBy->id,
            'variables_used'     => $variables,
            'file_path'          => null,
        ]);

        return [
            'bytes'       => $pdf->output(),
            'filename'    => $this->safeFilename(
                $student->full_name . '-' . $certificateNumber . '.pdf'
            ),
            'certificate' => $cert,
        ];
    }

    /**
     * Build a single student ID card PDF as raw bytes (no DB row, no disk).
     *
     * @return array{bytes:string, filename:string}
     */
    public function buildStudentIdCardPdf(IdCardTemplate $template, Student $student): array
    {
        $variables = $this->resolveStudentVariables($student);
        $variables['photo_url'] = $this->resolveStudentPhotoUrl($student);
        $variables['barcode'] = (string) $student->admission_number;

        // Verification URL + QR for the BACK of the card. The QR encodes
        // a public URL that opens the student's verification page so
        // anyone scanning the card can confirm the holder is enrolled.
        $variables['verification_url'] = $this->verificationUrlForStudent($student);
        $variables['verification_qr']  = $this->qrImageDataUri($variables['verification_url']);

        $html = $this->wrapPrintable($this->renderTemplate($template->html_template, $variables));

        $pdf = Pdf::loadHTML($html)
            ->setPaper($this->idCardPaper(), 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return [
            'bytes'    => $pdf->output(),
            'filename' => $this->safeFilename(
                ($student->admission_number ?: $student->id) . '-' . $student->full_name . '.pdf'
            ),
        ];
    }

    /**
     * Build a single staff ID card PDF as raw bytes.
     *
     * @return array{bytes:string, filename:string}
     */
    public function buildStaffIdCardPdf(IdCardTemplate $template, StaffProfile $staff): array
    {
        $user = $staff->schoolUser;
        $variables = [
            'full_name'    => $user?->name ?? '',
            'email'        => $user?->email ?? '',
            'phone'        => $user?->phone ?? '',
            'department'   => $staff->department?->name ?? '',
            'designation'  => $staff->designation?->title ?? '',
            'employee_id'  => $staff->employee_id ?? '',
            'joining_date' => $staff->joining_date?->format('d M Y') ?? '',
            'photo_url'    => $this->resolveStaffPhotoUrl($staff),
            'barcode'      => $staff->employee_id ?? ($user?->id ?? ''),
            'school_name'  => tenant()?->school_name ?? config('app.name'),
        ];

        $html = $this->wrapPrintable($this->renderTemplate($template->html_template, $variables));

        $pdf = Pdf::loadHTML($html)
            ->setPaper($this->idCardPaper(), 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $employeeId = $staff->employee_id ?? Str::limit((string) ($user?->id ?? 'staff'), 8, '');

        return [
            'bytes'    => $pdf->output(),
            'filename' => $this->safeFilename($employeeId . '-' . ($user?->name ?? 'staff') . '.pdf'),
        ];
    }

    /**
     * Bundle PDFs for many students into a ZIP archive (in-memory bytes).
     * One PDF per student. Returns null when collection is empty.
     *
     * @param  Collection<int,array{bytes:string,filename:string}>  $items
     */
    public function bundleAsZip(Collection $items, string $zipName): ?array
    {
        if ($items->isEmpty()) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kxedu-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $usedNames = [];
        foreach ($items as $item) {
            $name = $item['filename'];
            $i = 1;
            while (isset($usedNames[$name])) {
                $name = preg_replace('/\.pdf$/', "-{$i}.pdf", $item['filename']);
                $i++;
            }
            $usedNames[$name] = true;
            $zip->addFromString($name, $item['bytes']);
        }
        $zip->close();

        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        return [
            'bytes'    => $bytes,
            'filename' => $this->safeFilename($zipName . '.zip'),
        ];
    }

    // ── Backwards-compat shims (still used elsewhere) ─────────────────

    /**
     * Legacy: stores a generated certificate to disk and returns the model.
     * Retained for callers that still expect a stored row with file_path.
     */
    public function generateCertificate(
        CertificateTemplate $template,
        Student $student,
        SchoolUser $issuedBy,
        array $extraVariables = [],
    ): GeneratedCertificate {
        $built = $this->buildCertificatePdf($template, $student, $issuedBy, $extraVariables);

        $filename = "certificates/{$built['certificate']->certificate_number}.pdf";
        Storage::disk('tenant')->put($filename, $built['bytes']);

        $built['certificate']->update(['file_path' => $filename]);

        return $built['certificate']->fresh();
    }

    public function generateBulkCertificates(
        CertificateTemplate $template,
        Collection $students,
        SchoolUser $issuedBy,
        array $extraVariables = [],
    ): Collection {
        return $students->map(
            fn (Student $s) => $this->generateCertificate($template, $s, $issuedBy, $extraVariables),
        );
    }

    public function generateStudentIdCard(IdCardTemplate $template, Student $student): string
    {
        $built = $this->buildStudentIdCardPdf($template, $student);
        $filename = "id-cards/student-{$student->admission_number}-" . now()->format('Ymd') . '.pdf';
        Storage::disk('tenant')->put($filename, $built['bytes']);
        return $filename;
    }

    public function generateStaffIdCard(IdCardTemplate $template, StaffProfile $staff): string
    {
        $built = $this->buildStaffIdCardPdf($template, $staff);
        $employeeId = $staff->employee_id ?? Str::limit((string) ($staff->schoolUser?->id ?? 'staff'), 8, '');
        $filename = "id-cards/staff-{$employeeId}-" . now()->format('Ymd') . '.pdf';
        Storage::disk('tenant')->put($filename, $built['bytes']);
        return $filename;
    }

    public function generateBulkStudentIdCards(IdCardTemplate $template, Collection $students): string
    {
        $items = $students->map(fn (Student $s) => $this->buildStudentIdCardPdf($template, $s));
        $bundle = $this->bundleAsZip($items, 'student-id-cards-' . now()->format('Ymd-His'));
        $filename = 'id-cards/' . $bundle['filename'];
        Storage::disk('tenant')->put($filename, $bundle['bytes']);
        return $filename;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * dompdf works best when the body wraps its contents in a normal
     * document. Some templates ship raw <div>; some ship full <html>.
     * Detect and wrap.
     */
    private function wrapPrintable(string $html): string
    {
        if (stripos($html, '<html') !== false) {
            return $html;
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . 'body{margin:0;padding:0;font-family:DejaVu Sans,Arial,Helvetica,sans-serif;}'
            . '*{box-sizing:border-box;}'
            . '</style></head><body>' . $html . '</body></html>';
    }

    /**
     * If the certificate template doesn't already include a verification
     * marker, append a small footer block (school name + QR + URL).
     */
    private function ensureVerificationFooter(string $html): string
    {
        if (str_contains($html, '{{verification_qr}}') || str_contains($html, '{{ verification_qr }}')) {
            return $html;
        }

        $footer = <<<'HTML'

<div style="margin-top:30px;border-top:1px solid #999;padding-top:12px;font-size:11px;color:#444;font-family:DejaVu Sans,Arial,sans-serif;">
    <table style="width:100%;border-collapse:collapse;"><tr>
        <td style="vertical-align:top;width:88px;"><img src="{{verification_qr}}" alt="QR" style="width:80px;height:80px;"></td>
        <td style="vertical-align:top;padding-left:12px;">
            <div><strong>Issued by:</strong> {{school_name}}</div>
            <div><strong>Certificate No:</strong> {{certificate_number}}</div>
            <div><strong>Issued to:</strong> {{student_name}} (Reg #{{registration_number}})</div>
            <div style="margin-top:4px;">Verify online: <span style="font-family:monospace;">{{verification_url}}</span></div>
        </td>
    </tr></table>
</div>
HTML;

        return $html . $footer;
    }

    /**
     * Resolve all template variables for a student.
     */
    protected function resolveStudentVariables(Student $student, array $extra = []): array
    {
        $student->loadMissing(['schoolClass', 'section', 'academicYear', 'guardians', 'campus']);

        $guardian = $student->guardians->first();

        $variables = [
            'student_name'        => $student->full_name,
            'first_name'          => $student->first_name,
            'last_name'           => $student->last_name,
            'admission_number'    => $student->admission_number ?? '',
            'registration_number' => $student->registration_number ?? '',
            'roll_number'         => $student->roll_number ?? '',
            'class_name'          => $student->schoolClass?->name ?? '',
            'section_name'        => $student->section?->name ?? '',
            'academic_year'       => $student->academicYear?->name ?? '',
            'campus_name'         => $student->campus?->name ?? '',
            'date_of_birth'       => $student->date_of_birth?->format('d M Y') ?? '',
            'gender'              => $student->gender ?? '',
            'father_name'         => $guardian?->father_name ?? '',
            'mother_name'         => $guardian?->mother_name ?? '',
            'guardian_name'       => $guardian?->name ?? '',
            'guardian_phone'      => $guardian?->phone ?? '',
            'address'             => $student->current_address ?? $student->address ?? '',
            'blood_group'         => $student->blood_group ?? '',
            'religion'            => $student->religion ?? '',
            'nationality'         => $student->nationality ?? '',
            'school_name'         => tenant()?->school_name ?? config('app.name'),
            'current_date'        => now()->format('d M Y'),
        ];

        return array_merge($variables, $extra);
    }

    private function resolveStudentPhotoUrl(Student $student): string
    {
        $path = $student->profile_photo_path ?? $student->photo_path ?? null;
        if ($path) {
            try {
                return Storage::disk('tenant')->url($path);
            } catch (\Throwable) {
                // fall through
            }
        }
        return $this->fallbackAvatarDataUri();
    }

    private function resolveStaffPhotoUrl(StaffProfile $staff): string
    {
        $path = $staff->photo_path ?? null;
        if ($path) {
            try {
                return Storage::disk('tenant')->url($path);
            } catch (\Throwable) {
                // fall through
            }
        }
        return $this->fallbackAvatarDataUri();
    }

    /**
     * Tiny inline placeholder avatar so dompdf never errors on a missing
     * remote photo. 1×1 grey PNG, base64.
     */
    private function fallbackAvatarDataUri(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    /**
     * Replace placeholders in template HTML with actual values.
     */
    protected function renderTemplate(string $html, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $stringValue = match (true) {
                $value instanceof \BackedEnum  => (string) $value->value,
                $value instanceof \UnitEnum    => $value->name,
                $value instanceof \DateTimeInterface => $value->format('d M Y'),
                is_object($value) && method_exists($value, '__toString') => (string) $value,
                is_array($value)               => implode(', ', $value),
                $value === null                => '',
                default                        => (string) $value,
            };
            $html = str_replace('{{' . $key . '}}', $stringValue, $html);
            $html = str_replace('{{ ' . $key . ' }}', $stringValue, $html);
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

    /**
     * Verification URL for a certificate. Includes ?tenant= so the public
     * verify page can initialize the right tenant DB on the central domain.
     */
    public function verificationUrlFor(string $certificateNumber): string
    {
        $tenantId = tenancy()->initialized ? tenant()->id : null;
        $base = URL::to('/verify/certificate/' . urlencode($certificateNumber));
        return $tenantId ? $base . '?tenant=' . urlencode($tenantId) : $base;
    }

    /**
     * Verification URL for a student ID card. The QR on the back of the
     * card encodes this — scanning lands the visitor on a public page
     * that confirms the student is currently enrolled at the school.
     * Uses admission_number as the public identifier so the URL remains
     * stable even if internal IDs change.
     */
    public function verificationUrlForStudent(Student $student): string
    {
        $tenantId = tenancy()->initialized ? tenant()->id : null;
        $identifier = $student->registration_number ?: $student->admission_number ?: $student->id;
        $base = URL::to('/verify/student/' . urlencode($identifier));
        return $tenantId ? $base . '?tenant=' . urlencode($tenantId) : $base;
    }

    /**
     * Generate a base64 data URI containing a PNG QR code.
     */
    public function qrImageDataUri(string $url): string
    {
        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => QRCode::ECC_M,
                'scale'      => 5,
                'imageBase64' => true,
            ]);
            return (new QRCode($options))->render($url);
        } catch (\Throwable) {
            return $this->fallbackAvatarDataUri();
        }
    }

    /**
     * ID card paper size — 86×135mm portrait, scaled to points (72 dpi).
     * Slightly bigger than CR80 so the printable area can comfortably hold
     * a header + photo + details + a quick verification line.
     *
     * Width  = 86mm  ≈ 244pt
     * Height = 135mm ≈ 383pt
     */
    private function idCardPaper(): array
    {
        return [0, 0, 244, 383];
    }

    private function safeFilename(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        return trim((string) $clean, '_') ?: 'document.pdf';
    }
}
