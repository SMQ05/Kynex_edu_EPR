<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\GeneratedCertificate;
use App\Services\CertificateService;
use App\Support\SchoolSettings;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * The student's own ID card, plus any certificates issued to them.
 *
 * The QR is the real one: CertificateService::verificationUrlForStudent()
 * builds the same /verify/student/{id}?tenant={id} URL that gets printed on
 * the physical card, so scanning what is shown here hits the public
 * verification page and returns the same answer.
 */
class MyIdCard extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'My ID Card';

    protected string $view = 'filament.student-portal.pages.my-id-card';

    public function getHeading(): string
    {
        return 'My ID Card';
    }

    public function getSubheading(): ?string
    {
        return 'Scan the code to verify this card against the school\'s records.';
    }

    public function schoolName(): string
    {
        return (string) SchoolSettings::get('school.name', config('app.name'));
    }

    /** The verification URL encoded in the card's QR. */
    #[Computed]
    public function verifyUrl(): ?string
    {
        $student = $this->student();

        return $student
            ? app(CertificateService::class)->verificationUrlForStudent($student)
            : null;
    }

    /** The QR itself, as an inline PNG data URI. */
    #[Computed]
    public function qrDataUri(): ?string
    {
        $url = $this->verifyUrl;

        return $url ? app(CertificateService::class)->qrImageDataUri($url) : null;
    }

    /** Initials for the photo placeholder. */
    public function initials(): string
    {
        $student = $this->student();
        if (! $student) {
            return '—';
        }

        return strtoupper(
            mb_substr((string) $student->first_name, 0, 1) . mb_substr((string) $student->last_name, 0, 1)
        );
    }

    /** Certificates issued to this student, each with its own verify link. */
    #[Computed]
    public function certificates(): Collection
    {
        $service = app(CertificateService::class);

        return GeneratedCertificate::query()
            ->with('template')
            ->where('student_id', $this->studentId())
            ->orderByDesc('issued_date')
            ->get()
            ->map(function (GeneratedCertificate $c) use ($service) {
                $c->setAttribute('verify_url', $service->verificationUrlFor((string) $c->certificate_number));

                return $c;
            });
    }
}
