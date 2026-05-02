<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentApplication;
use App\Models\Tenant\StudentGuardian;
use App\Services\StudentAccountActivator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Public-facing student admission lifecycle. Mounted under tenant
 * subdomains so the form lives at e.g. https://demo.kynexedu/apply.
 */
class PublicAdmissionController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $this->ensureTenant($request);

        if (! $tenant) {
            return view('public.tenant-picker', [
                'tenants' => \App\Models\Tenant::orderBy('school_name')->get(['id', 'school_name']),
                'next'    => 'apply',
            ]);
        }

        return view('public.apply', [
            'tenant'   => $tenant,
            'classes'  => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
            'years'    => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'),
        ]);
    }

    /**
     * Initialise tenancy from the subdomain if present, otherwise from a
     * `?tenant=<id>` query string. Returns the resolved Tenant or null
     * when the public host is the central domain with no hint.
     */
    protected function ensureTenant(Request $request): ?\App\Models\Tenant
    {
        if (tenancy()->initialized) {
            return tenant();
        }

        $hint = $request->query('tenant');
        if (! $hint) {
            return null;
        }

        $tenant = \App\Models\Tenant::find($hint);
        if (! $tenant) {
            return null;
        }

        tenancy()->initialize($tenant);
        return $tenant;
    }

    public function submit(Request $request)
    {
        $tenant = $this->ensureTenant($request);
        if (! $tenant) {
            return redirect()->route('public.apply');
        }

        $validated = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'date_of_birth'   => 'nullable|date|before:today',
            'gender'          => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'father_name'     => 'nullable|string|max:255',
            'mother_name'     => 'nullable|string|max:255',
            'guardian_phone'  => 'required|string|max:20',
            'guardian_email'  => 'nullable|email|max:255',
            'previous_school' => 'nullable|string|max:255',
            'class_id'        => 'nullable|string|exists:classes,id',
            'campus_id'       => 'nullable|string|exists:campuses,id',
            'academic_year_id' => 'nullable|string|exists:academic_years,id',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $application = StudentApplication::create(array_merge($validated, [
            'status'       => ApplicationStatus::Submitted,
            'public_token' => Str::random(40),
        ]));

        return redirect()->route('public.apply.status', ['token' => $application->public_token])
            ->with('success', 'Application submitted. Save the link on this page to track status.');
    }

    public function status(string $token)
    {
        $application = StudentApplication::where('public_token', $token)->firstOrFail();

        return view('public.apply-status', [
            'tenant'      => tenant(),
            'application' => $application,
        ]);
    }

    /**
     * Public parent self-signup. Verifies that an admitted student exists
     * with the given admission/registration number and that the requesting
     * email matches one of that student's listed guardians, then sends a
     * set-password activation link.
     */
    public function showParentRegister(Request $request)
    {
        $tenant = $this->ensureTenant($request);
        if (! $tenant) {
            return view('public.tenant-picker', [
                'tenants' => \App\Models\Tenant::orderBy('school_name')->get(['id', 'school_name']),
                'next'    => 'parent.register',
            ]);
        }
        return view('public.parent-register', ['tenant' => $tenant]);
    }

    public function submitParentRegister(Request $request)
    {
        $tenant = $this->ensureTenant($request);
        if (! $tenant) {
            return redirect()->route('public.parent.register');
        }

        $validated = $request->validate([
            'student_reference' => 'required|string|max:60',
            'guardian_email'    => 'required|email|max:255',
        ]);

        $ref = trim($validated['student_reference']);
        $email = strtolower(trim($validated['guardian_email']));

        $student = Student::query()
            ->where('admission_number', $ref)
            ->orWhere('registration_number', $ref)
            ->first();

        if (! $student) {
            return back()->withInput()->withErrors([
                'student_reference' => 'No student matches that admission or registration number.',
            ]);
        }

        $guardian = StudentGuardian::query()
            ->where('student_id', $student->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $guardian) {
            return back()->withInput()->withErrors([
                'guardian_email' => 'No guardian with this email is on record for that student. Ask the school office to add you as a guardian first.',
            ]);
        }

        $user = SchoolUser::firstOrCreate(
            ['email' => $email],
            [
                'name'      => $guardian->name,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => false,
            ],
        );

        if (! $user->hasRole('PARENT')) {
            $user->assignRole('PARENT');
        }

        if (! $guardian->school_user_id) {
            $guardian->update(['school_user_id' => $user->id]);
        }

        app(StudentAccountActivator::class)->activateGuardian(
            $guardian->name,
            $email,
            tenant()->id,
            ['student_id' => $student->id, 'school_user_id' => $user->id],
        );

        return view('public.parent-register-sent', [
            'tenant' => tenant(),
            'email'  => $email,
        ]);
    }
}
