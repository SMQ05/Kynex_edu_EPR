<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Complete Your Admission Profile — {{ $tenant?->school_name ?? 'School' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root {
        --grad-brand: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%);
        --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
    }
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
    .field-input {
        display: block; width: 100%;
        border-radius: 0.75rem; border: 0;
        background: #fff; padding: 0.75rem 1rem;
        min-height: 48px;
        color: #0f172a; font-size: 16px; line-height: 1.5;
        box-shadow: inset 0 0 0 1px #cbd5e1;
        transition: box-shadow 0.15s ease, background 0.15s ease; outline: none;
    }
    .field-input::placeholder { color: #94a3b8; }
    .field-input:focus { box-shadow: inset 0 0 0 2px #2563eb, 0 0 0 4px rgba(37,99,235,.12); }
    .field-input.ring-red-400 { box-shadow: inset 0 0 0 2px #f87171; background: #fff5f5; }
    .field-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem; }
    .field-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; display: block; }
    textarea.field-input { min-height: 0; resize: none; }
    select.field-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center; background-size: 18px; padding-right: 2.5rem;
    }
    .grad-brand { background: var(--grad-brand); }
    .btn-grad { background: var(--grad-brand); box-shadow: 0 16px 40px -12px rgba(37,99,235,.45); transition: transform .15s, box-shadow .2s, filter .2s; }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 20px 48px -12px rgba(37,99,235,.6); filter: brightness(1.04); }
    @media (prefers-reduced-motion: reduce) { .btn-grad { transition: none; } }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(30rem 30rem at 85% -20%, rgba(6,182,212,.45), transparent 60%), radial-gradient(24rem 24rem at 0% 120%, rgba(124,58,237,.4), transparent 55%);"></div>
    <div class="relative max-w-3xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur flex items-center justify-center text-white font-black text-xl flex-shrink-0">
                {{ mb_substr($tenant?->school_name ?? 'S', 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="font-extrabold text-lg sm:text-xl leading-tight truncate">{{ $tenant?->school_name ?? 'School' }}</div>
                <div class="text-xs sm:text-sm text-white/70">Admissions</div>
            </div>
        </div>
        <h1 class="mt-5 text-2xl sm:text-3xl font-extrabold tracking-tight">Complete Your Student Profile</h1>
        <p class="mt-1.5 text-sm sm:text-base text-white/80 max-w-lg">A few more details to finalise enrolment and activate the parent portal.</p>
    </div>
</header>

<div class="max-w-3xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">

    {{-- Success state --}}
    @if(session('profile_saved') || $completed)
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-emerald-200 p-8 text-center">
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 flex items-center justify-center text-4xl">🎉</div>
            <h1 class="text-2xl font-extrabold tracking-tight text-emerald-700 mb-2">Profile Complete!</h1>
            <p class="text-slate-600 leading-relaxed">Your student profile has been saved successfully. The admissions office will review and activate your account shortly.</p>
            <div class="mt-6 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-4 text-sm text-emerald-800 inline-block">
                Admission Number: <strong>{{ $student->admission_number }}</strong>
            </div>
        </div>
        @php return; @endphp
    @endif

    {{-- Admission info banner --}}
    <div class="rounded-2xl bg-blue-50 ring-1 ring-blue-200 p-4 mb-6 flex items-start gap-3">
        <div class="text-xl mt-0.5">📋</div>
        <div class="text-sm text-blue-900 leading-relaxed">
            <strong>Completing profile for {{ $student->first_name }} {{ $student->last_name }}</strong> ·
            Admission No. <strong>{{ $student->admission_number }}</strong> ·
            {{ $student->schoolClass?->name ?? 'Class N/A' }}
            @if($student->section?->name) — Section {{ $student->section->name }}@endif
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-2xl bg-red-50 ring-1 ring-red-200 p-4 text-sm text-red-800">
            <p class="font-bold mb-1">Please fix the following:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.admission.complete.submit', ['token' => $application->public_token]) }}">
        @csrf

        {{-- ── Section 1: Personal Details ──────────────────────── --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-5">
            <div class="flex items-center gap-3.5 mb-6">
                <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">👤</div>
                <div>
                    <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Personal Details</h2>
                    <p class="text-xs text-slate-500">Medical and identification information</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="field-label" for="blood_group">Blood group <span class="text-red-500">*</span></label>
                    <select id="blood_group" name="blood_group" required class="field-input @error('blood_group') ring-red-400 @enderror">
                        <option value="">Select blood group</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" @selected(old('blood_group', $student->blood_group?->value) === $bg)>{{ $bg }}</option>
                        @endforeach
                    </select>
                    @error('blood_group')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="religion">Religion <span class="text-red-500">*</span></label>
                    <input id="religion" name="religion" required value="{{ old('religion', $student->religion) }}"
                           placeholder="e.g. Islam"
                           class="field-input @error('religion') ring-red-400 @enderror">
                    @error('religion')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="nationality">Nationality <span class="text-red-500">*</span></label>
                    <input id="nationality" name="nationality" required value="{{ old('nationality', $student->nationality ?? 'Pakistani') }}"
                           placeholder="e.g. Pakistani"
                           class="field-input @error('nationality') ring-red-400 @enderror">
                    @error('nationality')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="cnic_bform">CNIC / B-Form number <span class="text-red-500">*</span></label>
                    <input id="cnic_bform" name="cnic_bform" required value="{{ old('cnic_bform') }}"
                           placeholder="e.g. 35201-1234567-8"
                           class="field-input @error('cnic_bform') ring-red-400 @enderror">
                    @error('cnic_bform')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label" for="medical_notes">Medical conditions / allergies <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea id="medical_notes" name="medical_notes" rows="2"
                              placeholder="e.g. Asthma, peanut allergy…"
                              class="field-input @error('medical_notes') ring-red-400 @enderror">{{ old('medical_notes', $student->medical_notes) }}</textarea>
                    @error('medical_notes')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="special_needs_notes">Special needs / learning support <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea id="special_needs_notes" name="special_needs_notes" rows="2"
                              placeholder="Any learning or physical support required…"
                              class="field-input @error('special_needs_notes') ring-red-400 @enderror">{{ old('special_needs_notes', $student->special_needs_notes) }}</textarea>
                    @error('special_needs_notes')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Section 2: Father / Guardian ──────────────────────── --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-5">
            <div class="flex items-center gap-3.5 mb-6">
                <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">👨</div>
                <div>
                    <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Father / Primary Guardian</h2>
                    <p class="text-xs text-slate-500">Required for school records and portal access</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="field-label" for="father_name">Full name <span class="text-red-500">*</span></label>
                    <input id="father_name" name="father_name" required value="{{ old('father_name', $father?->name ?? $application->father_name) }}"
                           placeholder="Full legal name"
                           class="field-input @error('father_name') ring-red-400 @enderror">
                    @error('father_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="father_cnic">CNIC number <span class="text-red-500">*</span></label>
                    <input id="father_cnic" name="father_cnic" required value="{{ old('father_cnic', $father?->cnic) }}"
                           placeholder="e.g. 35201-1234567-8"
                           class="field-input @error('father_cnic') ring-red-400 @enderror">
                    @error('father_cnic')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="father_phone">Phone number <span class="text-red-500">*</span></label>
                    <input id="father_phone" name="father_phone" type="tel" required value="{{ old('father_phone', $father?->phone ?? $application->guardian_phone) }}"
                           placeholder="e.g. 03001234567"
                           class="field-input @error('father_phone') ring-red-400 @enderror">
                    @error('father_phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="father_whatsapp">WhatsApp number <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input id="father_whatsapp" name="father_whatsapp" type="tel" value="{{ old('father_whatsapp', $father?->whatsapp) }}"
                           placeholder="Leave blank if same as phone"
                           class="field-input @error('father_whatsapp') ring-red-400 @enderror">
                    @error('father_whatsapp')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="father_email">Email address <span class="text-slate-400 font-normal">(for parent portal)</span></label>
                    <input type="email" id="father_email" name="father_email" value="{{ old('father_email', $father?->email ?? $application->guardian_email) }}"
                           placeholder="father@example.com"
                           class="field-input @error('father_email') ring-red-400 @enderror">
                    @error('father_email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="father_occupation">Occupation <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input id="father_occupation" name="father_occupation" value="{{ old('father_occupation', $father?->occupation) }}"
                           placeholder="e.g. Engineer, Teacher…"
                           class="field-input @error('father_occupation') ring-red-400 @enderror">
                    @error('father_occupation')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Section 3: Mother ─────────────────────────────────── --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-5">
            <div class="flex items-center gap-3.5 mb-6">
                <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">👩</div>
                <div>
                    <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Mother <span class="text-slate-400 font-normal text-sm">(optional)</span></h2>
                    <p class="text-xs text-slate-500">Leave blank if not applicable</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="sm:col-span-1">
                    <label class="field-label" for="mother_name">Full name</label>
                    <input id="mother_name" name="mother_name" value="{{ old('mother_name', $mother?->name ?? $application->mother_name) }}"
                           placeholder="Full legal name"
                           class="field-input @error('mother_name') ring-red-400 @enderror">
                    @error('mother_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="mother_cnic">CNIC number</label>
                    <input id="mother_cnic" name="mother_cnic" value="{{ old('mother_cnic', $mother?->cnic) }}"
                           placeholder="e.g. 35201-7654321-0"
                           class="field-input @error('mother_cnic') ring-red-400 @enderror">
                    @error('mother_cnic')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="mother_phone">Phone number</label>
                    <input id="mother_phone" name="mother_phone" type="tel" value="{{ old('mother_phone', $mother?->phone) }}"
                           placeholder="e.g. 03001234567"
                           class="field-input @error('mother_phone') ring-red-400 @enderror">
                    @error('mother_phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Section 4: Emergency Contact ─────────────────────── --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-5">
            <div class="flex items-center gap-3.5 mb-6">
                <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">🚨</div>
                <div>
                    <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Emergency Contact</h2>
                    <p class="text-xs text-slate-500">Person to contact in an emergency (can be same as father)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="field-label" for="emergency_name">Full name <span class="text-red-500">*</span></label>
                    <input id="emergency_name" name="emergency_name" required value="{{ old('emergency_name') }}"
                           placeholder="Contact person's name"
                           class="field-input @error('emergency_name') ring-red-400 @enderror">
                    @error('emergency_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="emergency_phone">Phone number <span class="text-red-500">*</span></label>
                    <input id="emergency_phone" name="emergency_phone" type="tel" required value="{{ old('emergency_phone') }}"
                           placeholder="e.g. 03001234567"
                           class="field-input @error('emergency_phone') ring-red-400 @enderror">
                    @error('emergency_phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label" for="emergency_relation">Relationship <span class="text-red-500">*</span></label>
                    <input id="emergency_relation" name="emergency_relation" required value="{{ old('emergency_relation') }}"
                           placeholder="e.g. Father, Uncle, Aunt"
                           class="field-input @error('emergency_relation') ring-red-400 @enderror">
                    @error('emergency_relation')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-4 text-sm text-amber-800 mb-6 leading-relaxed">
            📄 <strong>Remember to also bring the following documents to school:</strong>
            Original birth certificate, 4 passport photos, father's CNIC copy, previous school result card, and transfer/leaving certificate.
        </div>

        <button type="submit"
            class="btn-grad w-full rounded-xl text-white font-semibold py-3.5 min-h-[52px] text-base flex items-center justify-center gap-2">
            ✅ Save &amp; Complete My Profile
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>
</body>
</html>
