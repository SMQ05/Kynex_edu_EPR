<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\GeneratedCertificate;
use Illuminate\Contracts\View\View;

/**
 * Public-facing certificate verification.
 *
 * The QR code embedded in every issued certificate points at this controller
 * with the certificate number in the path. The route is registered behind
 * InitializeTenancyBySubdomain so the tenant DB is initialised from either
 * the subdomain or a ?tenant= query string, which is what the QR encodes.
 *
 * The page is intentionally minimal: school name + statement that confirms
 * the certificate was issued, identifying the recipient by name and
 * registration number. Nothing else is exposed.
 */
class CertificateVerificationController extends Controller
{
    public function show(string $number): View
    {
        $certificate = GeneratedCertificate::query()
            ->with(['template', 'student'])
            ->where('certificate_number', $number)
            ->first();

        $tenant = function_exists('tenant') ? tenant() : null;
        $schoolName = $tenant?->school_name ?? config('app.name', 'School');

        return view('public.certificate-verify', [
            'certificate' => $certificate,
            'schoolName'  => $schoolName,
            'requestedNumber' => $number,
        ]);
    }
}
