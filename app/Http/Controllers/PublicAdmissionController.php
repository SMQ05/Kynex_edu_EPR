<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Jobs\SendExamCredentialsBatch;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AdmissionSession;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentApplication;
use App\Models\Tenant\StudentGuardian;
use App\Services\StudentAccountActivator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Resend\Client as ResendClient;

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

        // Honour the school's admission_open flag — when closed, render
        // a clean "Admissions are not open" page instead of the form.
        $settings = \App\Models\Tenant\CmsSetting::getSettings();
        if (! ($settings->admission_open ?? false)) {
            return view('public.apply-closed', [
                'tenant'   => $tenant,
                'settings' => $settings,
            ]);
        }

        $classRows = SchoolClass::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'sort_order']);

        return view('public.apply', [
            'tenant'        => $tenant,
            'classes'       => $classRows->pluck('name', 'id'),
            // id → sort_order map so the form can decide when to require
            // previous-school marks (anything above Class 1 / sort_order > 1).
            'classSortOrder'=> $classRows->mapWithKeys(fn ($c) => [$c->id => (int) ($c->sort_order ?? 0)])->all(),
            'campuses'      => Campus::orderBy('name')->pluck('name', 'id'),
            'years'         => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'),
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

        // Conditional: previous-school marks are required when applying for
        // a class above Class 1 (sort_order > 1). Class 1 and below (KG etc.)
        // can skip them.
        $classId = $request->input('class_id');
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $requirePrevMarks = $selectedClass && (int) ($selectedClass->sort_order ?? 0) > 1;
        $prevMarksRule = $requirePrevMarks ? 'required' : 'nullable';

        $validated = $request->validate([
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'date_of_birth'        => 'required|date|before:today',
            'gender'               => ['required', Rule::in(['male', 'female', 'other'])],
            'phone'                => 'required|string|max:20',
            'email'                => 'required|email|max:255',
            // Pakistani CNIC / B-Form: 13 numeric digits, no dashes.
            // Hard-block dedup: each student can only apply once.
            'student_cnic'         => [
                'required',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('student_applications', 'student_cnic')->whereNull('deleted_at'),
            ],
            'parent_cnic'          => 'nullable|string|regex:/^\d{13}$/',
            'address'              => 'required|string|max:500',
            'city'                 => 'required|string|max:100',
            'father_name'          => 'required|string|max:255',
            'mother_name'          => 'nullable|string|max:255',
            'guardian_phone'       => 'required|string|max:20',
            'guardian_email'       => 'required|email|max:255',
            'previous_school'      => 'required|string|max:255',
            'previous_school_score'=> $prevMarksRule . '|numeric|min:0',
            'previous_score_max'   => $prevMarksRule . '|numeric|min:1',
            'class_id'             => 'required|string|exists:classes,id',
            'campus_id'            => 'nullable|string|exists:campuses,id',
            'academic_year_id'     => 'nullable|string|exists:academic_years,id',
            'notes'                => 'nullable|string|max:2000',
            'applicant_photo'      => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'student_cnic.unique'  => 'A student with this B-Form / CNIC has already applied. Each student can apply only once. If you believe this is an error, contact the school office.',
            'student_cnic.regex'   => 'B-Form / CNIC must be exactly 13 digits with no dashes or spaces.',
            'parent_cnic.regex'    => 'Parent CNIC must be exactly 13 digits with no dashes or spaces.',
            'previous_school_score.required' => 'Marks obtained at previous school is required for classes above Class 1.',
            'previous_score_max.required'    => 'Total marks at previous school is required for classes above Class 1.',
        ]);

        // Store photo if provided.
        $photoPath = null;
        if ($request->hasFile('applicant_photo') && $request->file('applicant_photo')->isValid()) {
            $photoPath = $request->file('applicant_photo')->store('applicant-photos', 'public');
        }

        $application = StudentApplication::create(array_merge($validated, [
            'status'          => ApplicationStatus::Submitted,
            'public_token'    => Str::random(40),
            'applicant_photo' => $photoPath,
        ]));

        // Notify school admin + institute head — visible in the bell
        // icon. Wrapped in try/catch because legacy tenants may not
        // have the in_app_notifications table or have schema drift.
        try {
            $recipients = \App\Models\SchoolUser::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'REGISTRAR']))
                ->where('is_active', true)
                ->get();

            foreach ($recipients as $r) {
                \App\Models\Tenant\InAppNotification::create([
                    'user_id' => $r->id,
                    'title'   => 'New admission application',
                    'body'    => "{$application->full_name} has applied online. Open Students → Admissions to review.",
                    'type'    => 'info',
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Application notification failed', ['error' => $e->getMessage()]);
        }

        // ── Confirmation email ──────────────────────────────────────
        // The status route lives behind tenant middleware. When the URL is
        // generated on the central host (no tenant context), the click would
        // 404 because tenancy never initializes. Always append ?tenant=ID so
        // the status() handler can resolve the tenant on either host.
        $statusUrl = route('public.apply.status', ['token' => $application->public_token])
            . '?tenant=' . urlencode($tenant->id);

        $this->sendApplicationConfirmation($application, $tenant, $statusUrl);

        // ── Auto-assign to existing scheduled session ───────────────
        // If there's an upcoming test session for this class (or all classes),
        // slot the applicant in now and send their exam credentials immediately.
        try {
            $session = AdmissionSession::where('type', 'test')
                ->where('academic_year_id', $application->academic_year_id)
                ->where(fn ($q) => $q->whereNull('class_id')
                    ->orWhere('class_id', $application->class_id))
                ->whereNull('exam_started_at')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at')
                ->first();

            if ($session && $session->assignedCount() < $session->max_capacity) {
                $application->update(['test_session_id' => $session->id]);
                SendExamCredentialsBatch::dispatch(tenant()->id, $session->id, 0, 10);
            }
        } catch (\Throwable $e) {
            Log::warning('Auto-session assignment failed for new application', [
                'application_id' => $application->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return redirect($statusUrl)
            ->with('success', 'Application submitted. Save the link on this page to track status.');
    }

    private function sendApplicationConfirmation(
        StudentApplication $application,
        \App\Models\Tenant $tenant,
        string $statusUrl,
    ): void {
        $applicantEmail = filled($application->email) ? strtolower(trim($application->email)) : null;
        $guardianEmail  = filled($application->guardian_email) ? strtolower(trim($application->guardian_email)) : null;

        if (! $applicantEmail && ! $guardianEmail) {
            return;
        }

        $resend     = app(ResendClient::class);
        $schoolName = $tenant->school_name ?? 'School';
        $from       = config('mail.from.name', 'KynexEdu') . ' <' . config('mail.from.address', 'noreply@kynexsolutions.com') . '>';

        $className  = $application->class_id ? SchoolClass::find($application->class_id)?->name  : null;
        $campusName = $application->campus_id ? Campus::find($application->campus_id)?->name : null;

        $shared = [
            'schoolName'     => $schoolName,
            'applicantName'  => $application->full_name,
            'referenceToken' => $application->public_token,
            'statusUrl'      => $statusUrl,
            'className'      => $className,
            'campusName'     => $campusName,
            'appliedDate'    => $application->created_at->format('d M Y'),
        ];

        // 1) Email the applicant (student) — "Dear NAME, thank you for applying"
        if ($applicantEmail) {
            try {
                $html = view('emails.application-received', $shared)->render();
                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $applicantEmail,
                    'subject' => 'Application Received — ' . $schoolName,
                    'html'    => $html,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Application confirmation email (applicant) failed', [
                    'application_id' => $application->id,
                    'to'             => $applicantEmail,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        // 2) Email the guardian — "Dear Parent, your son/daughter NAME has applied"
        // Skip if the guardian email is identical to the applicant email
        // (otherwise the same address would receive two different copies).
        if ($guardianEmail && $guardianEmail !== $applicantEmail) {
            try {
                $html = view('emails.application-received-guardian', $shared)->render();
                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $guardianEmail,
                    'subject' => 'Application Received for ' . $application->full_name . ' — ' . $schoolName,
                    'html'    => $html,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Application confirmation email (guardian) failed', [
                    'application_id' => $application->id,
                    'to'             => $guardianEmail,
                    'error'          => $e->getMessage(),
                ]);
            }
        }
    }

    public function status(Request $request, string $token)
    {
        // Resolve tenancy if not already initialized (e.g. when the URL is
        // opened on the central host, the middleware passes through without
        // initializing — we must do it here from the ?tenant= query).
        $tenant = $this->ensureTenant($request);
        if (! $tenant) {
            abort(404, 'School not found. Please open the link from your application email.');
        }

        $application = StudentApplication::where('public_token', $token)->first();
        if (! $application) {
            abort(404, 'Application not found. Check the link from your email or contact the school.');
        }

        return view('public.apply-status', [
            'tenant'      => $tenant,
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
