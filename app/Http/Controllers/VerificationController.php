<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Tenant\GeneratedCertificate;
use App\Models\Tenant\Student;
use App\Support\EnumLabel;
use App\Support\SchoolSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public QR verification for student ID cards and issued certificates.
 *
 * CertificateService has always encoded QR codes pointing at
 * /verify/student/{identifier} and /verify/certificate/{number}, but no such
 * routes existed — so every QR on every card and certificate resolved to a
 * 404. These endpoints are that missing half.
 *
 * Deliberately public and unauthenticated: the point of a QR on a printed
 * card is that a stranger can check it. Equally deliberately minimal in what
 * it discloses — name, photo-less identity, class, status and issue date. It
 * must never become a way to enumerate a school's student body, so:
 *
 *   - the tenant must be named explicitly in the query string; there is no
 *     search across schools
 *   - a miss renders the same "not found" view as a malformed request, so the
 *     response cannot be used to probe which identifiers exist
 *   - no contact details, guardians, fees or marks are ever included
 */
class VerificationController extends Controller
{
    public function student(Request $request, string $identifier): View
    {
        return $this->withinTenant($request, function () use ($identifier) {
            $student = Student::query()
                ->with(['schoolClass', 'section'])
                ->where(fn ($q) => $q
                    ->where('registration_number', $identifier)
                    ->orWhere('admission_number', $identifier)
                    ->orWhere('id', $identifier))
                ->first();

            if (! $student) {
                return null;
            }

            return [
                'kind' => 'student',
                'valid' => true,
                'title' => 'Student ID verified',
                'subject' => trim($student->first_name . ' ' . $student->last_name),
                'rows' => array_filter([
                    'Student ID' => $student->admission_number ?: $student->registration_number,
                    'Class' => $student->schoolClass?->name,
                    'Section' => $student->section?->name,
                    // Native enum: see App\Support\EnumLabel for why casting throws.
                    'Status' => EnumLabel::text($student->status, 'Active'),
                    'Enrolled' => $student->admission_date
                        ? \Illuminate\Support\Carbon::parse($student->admission_date)->format('F j, Y')
                        : null,
                ]),
            ];
        });
    }

    public function certificate(Request $request, string $number): View
    {
        return $this->withinTenant($request, function () use ($number) {
            $certificate = GeneratedCertificate::query()
                ->with(['student.schoolClass', 'template'])
                ->where('certificate_number', $number)
                ->first();

            if (! $certificate) {
                return null;
            }

            $student = $certificate->student;

            return [
                'kind' => 'certificate',
                'valid' => true,
                'title' => 'Certificate verified',
                'subject' => $student
                    ? trim($student->first_name . ' ' . $student->last_name)
                    : 'Unknown recipient',
                'rows' => array_filter([
                    'Certificate no.' => $certificate->certificate_number,
                    'Type' => $certificate->template?->name,
                    'Issued to' => $student ? ($student->admission_number ?: null) : null,
                    'Class' => $student?->schoolClass?->name,
                    'Issued on' => $certificate->issued_date
                        ? \Illuminate\Support\Carbon::parse($certificate->issued_date)->format('F j, Y')
                        : null,
                ]),
            ];
        });
    }

    /**
     * Resolve the tenant named in ?tenant=, run the lookup inside its
     * database, and render the result.
     *
     * Tenancy is always ended afterwards, including on failure, so a bad
     * lookup cannot leave the connection pointed at a tenant database.
     */
    protected function withinTenant(Request $request, \Closure $lookup): View
    {
        $tenantId = (string) $request->query('tenant', '');
        $tenant = $tenantId !== '' ? Tenant::find($tenantId) : null;

        if (! $tenant) {
            return view('verify.result', [
                'valid' => false,
                'title' => 'Could not verify',
                'message' => 'This code is missing the school it belongs to, or that school no longer exists.',
                'school' => null,
                'subject' => null,
                'rows' => [],
            ]);
        }

        try {
            tenancy()->initialize($tenant);

            $result = $lookup();
            $schoolName = SchoolSettings::get('school.name', $tenant->school_name ?: 'This school');

            if ($result === null) {
                return view('verify.result', [
                    'valid' => false,
                    'title' => 'Could not verify',
                    'message' => 'No matching record was issued by this school. If this came from a printed card, check the code and try again.',
                    'school' => $schoolName,
                    'subject' => null,
                    'rows' => [],
                ]);
            }

            return view('verify.result', $result + [
                'school' => $schoolName,
                'message' => null,
            ]);
        } catch (\Throwable $e) {
            // Log it. A bare catch here once hid a genuine bug (casting the
            // StudentStatus enum to string) behind a friendly message, so the
            // page looked like a clean "not found" for a week.
            \Illuminate\Support\Facades\Log::error('QR verification failed', [
                'tenant' => $tenantId,
                'path' => $request->path(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return view('verify.result', [
                'valid' => false,
                'title' => 'Could not verify',
                'message' => 'Verification is temporarily unavailable. Please try again shortly.',
                'school' => null,
                'subject' => null,
                'rows' => [],
            ]);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }
}
