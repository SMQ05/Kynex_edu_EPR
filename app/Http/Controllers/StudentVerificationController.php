<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\Student;
use Illuminate\Contracts\View\View;

/**
 * Public student card verification.
 *
 * The QR code on the back of every student ID card encodes
 *   /verify/student/{admission_or_registration_number}?tenant={id}
 * Tenancy is initialised from the ?tenant= query string by
 * InitializeTenancyBySubdomain, then we look up the student by
 * registration_number first (more stable) then admission_number.
 *
 * The page is intentionally minimal — name, class, registration #,
 * status. Nothing else is exposed.
 */
class StudentVerificationController extends Controller
{
    public function show(string $identifier): View
    {
        $student = null;
        if (function_exists('tenant') && tenant()) {
            $student = Student::query()
                ->with(['schoolClass', 'section', 'campus', 'academicYear'])
                ->where(function ($q) use ($identifier) {
                    $q->where('registration_number', $identifier)
                      ->orWhere('admission_number', $identifier);
                })
                ->first();
        }

        $tenant = function_exists('tenant') ? tenant() : null;

        return view('public.student-verify', [
            'student'           => $student,
            'identifier'        => $identifier,
            'schoolName'        => $tenant?->school_name ?? config('app.name', 'School'),
            'logoPath'          => optional(\App\Models\Tenant\CmsSetting::query()->first())->logo_path,
        ]);
    }
}
