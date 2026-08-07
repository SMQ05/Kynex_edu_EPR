<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Admission Application — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root {
        --grad-brand: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%);
        --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
    }
    html { -webkit-text-size-adjust: 100%; }
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }

    .field-input {
        display: block; width: 100%;
        border-radius: 0.75rem; border: 0;
        background: #fff; padding: 0.75rem 1rem;
        min-height: 48px;
        color: #0f172a; font-size: 16px; line-height: 1.5;
        box-shadow: inset 0 0 0 1px #cbd5e1;
        transition: box-shadow 0.15s ease, background 0.15s ease;
        outline: none;
    }
    .field-input::placeholder { color: #94a3b8; }
    .field-input:focus { box-shadow: inset 0 0 0 2px #2563eb, 0 0 0 4px rgba(37,99,235,.12); }
    .field-input.invalid { box-shadow: inset 0 0 0 2px #f87171; background: #fff5f5; }
    .field-input.ring-red-400 { box-shadow: inset 0 0 0 2px #f87171; background: #fff5f5; }

    .field-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; display: block; }
    .field-error-msg { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; display: none; }
    .field-error-msg.visible { display: block; }

    @keyframes shake {
        0%,100% { transform: translateX(0); }
        15%,45%,75% { transform: translateX(-5px); }
        30%,60%,90% { transform: translateX(5px); }
    }
    .shake { animation: shake 0.4s ease; }

    .field-label {
        display: block; font-size: 0.875rem;
        font-weight: 600; color: #334155; margin-bottom: 0.375rem;
    }
    .field-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; display: block; }

    select.field-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center; background-size: 18px;
        padding-right: 2.5rem;
    }
    textarea.field-input { resize: none; min-height: 0; }

    .step-circle {
        width: 2.25rem; height: 2.25rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; font-weight: 800;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    .step-line { flex: 1; height: 2px; background: #e2e8f0; transition: background 0.3s ease; }
    .step-line.done { background: linear-gradient(90deg,#4f46e5,#06b6d4); }

    .step-panel { display: none; }
    .step-panel.active { display: block; animation: fadeIn 0.25s ease; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .grad-brand { background: var(--grad-brand); }
    .btn-grad {
        background: var(--grad-brand);
        box-shadow: 0 16px 40px -12px rgba(37,99,235,.45);
        transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
    }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 20px 48px -12px rgba(37,99,235,.6); filter: brightness(1.04); }
    .btn-grad:active { transform: translateY(0); }
    @media (prefers-reduced-motion: reduce) {
        .step-panel.active, .btn-grad, .shake { animation: none !important; transition: none !important; }
    }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

{{-- ══ Branded header band ════════════════════════════════════════ --}}
<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(30rem 30rem at 85% -20%, rgba(6,182,212,.45), transparent 60%), radial-gradient(24rem 24rem at 0% 120%, rgba(124,58,237,.4), transparent 55%);"></div>
    <div class="relative max-w-3xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur flex items-center justify-center text-white font-black text-xl flex-shrink-0">
                {{ mb_substr($tenant->school_name, 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="font-extrabold text-lg sm:text-xl leading-tight truncate">{{ $tenant->school_name }}</div>
                <div class="text-xs sm:text-sm text-white/70">Online Admissions</div>
            </div>
        </div>
        <h1 class="mt-5 text-2xl sm:text-3xl font-extrabold tracking-tight">Admission Application</h1>
        <p class="mt-1.5 text-sm sm:text-base text-white/80 max-w-lg">Complete the steps below to apply. It only takes a few minutes — no account required.</p>
    </div>
</header>

<div class="max-w-3xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">

    {{-- ══ Step indicator ══════════════════════════════════════════ --}}
    <div class="flex items-center mb-6 px-3 py-4 sm:py-5 rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-100" id="stepIndicator">
        @php $steps = ['Student','Guardian','Academics','Review']; @endphp
        @foreach($steps as $i => $label)
            <div class="flex flex-col items-center" style="min-width:56px">
                <div class="step-circle" id="sc-{{ $i+1 }}">{{ $i+1 }}</div>
                <span class="text-[11px] sm:text-xs mt-1.5 font-medium text-center" id="sl-{{ $i+1 }}">{{ $label }}</span>
            </div>
            @if(!$loop->last)
                <div class="step-line" id="line-{{ $i+1 }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ══ Error banner (server-side errors) ═════════════════════ --}}
    @if($errors->any())
        <div class="mb-5 rounded-2xl bg-red-50 ring-1 ring-red-200 p-4 text-sm text-red-800">
            <p class="font-bold mb-1">Please fix the following:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-5 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- ══ FORM ════════════════════════════════════════════════════ --}}
    <form method="POST" action="" id="admissionForm" enctype="multipart/form-data" novalidate>
        @csrf

        {{-- ─── Step 1: Student ─────────────────────────────────── --}}
        <div class="step-panel" id="step-1">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-4">
                <div class="flex items-center gap-3.5 mb-6">
                    <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">👤</div>
                    <div>
                        <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Student Information</h2>
                        <p class="text-xs text-slate-500">Basic details about the applicant</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="field-label" for="first_name">First name <span class="text-red-500">*</span></label>
                        <input id="first_name" name="first_name" required value="{{ old('first_name') }}"
                               placeholder="e.g. Ahmad"
                               class="field-input @error('first_name') ring-red-400 @enderror">
                        @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="last_name">Last name <span class="text-red-500">*</span></label>
                        <input id="last_name" name="last_name" required value="{{ old('last_name') }}"
                               placeholder="e.g. Khan"
                               class="field-input @error('last_name') ring-red-400 @enderror">
                        @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="date_of_birth">Date of birth <span class="text-red-500">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required value="{{ old('date_of_birth') }}"
                               class="field-input @error('date_of_birth') ring-red-400 @enderror">
                        @error('date_of_birth')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_date_of_birth">Date of birth is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="gender">Gender <span class="text-red-500">*</span></label>
                        <select id="gender" name="gender" required class="field-input @error('gender') ring-red-400 @enderror">
                            <option value="">Select gender</option>
                            <option value="male"   @selected(old('gender')==='male')>Male</option>
                            <option value="female" @selected(old('gender')==='female')>Female</option>
                            <option value="other"  @selected(old('gender')==='other')>Other</option>
                        </select>
                        @error('gender')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_gender">Please select a gender.</p>
                    </div>
                    <div>
                        <label class="field-label" for="phone">Phone number <span class="text-red-500">*</span></label>
                        <input id="phone" name="phone" type="tel" required value="{{ old('phone') }}"
                               placeholder="e.g. 03001234567"
                               class="field-input @error('phone') ring-red-400 @enderror">
                        @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_phone">Phone number is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="email">Email address <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}"
                               placeholder="student@example.com"
                               class="field-input @error('email') ring-red-400 @enderror">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_email">A valid email address is required.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="student_cnic">Student B-Form / CNIC <span class="text-red-500">*</span> <span class="text-slate-400 font-normal text-xs">(13 digits, no dashes)</span></label>
                        <input id="student_cnic" name="student_cnic" required
                               inputmode="numeric"
                               pattern="\d{13}"
                               maxlength="13"
                               value="{{ old('student_cnic') }}"
                               placeholder="e.g. 1234567890123"
                               class="field-input @error('student_cnic') ring-red-400 @enderror">
                        @error('student_cnic')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_student_cnic">A valid 13-digit B-Form / CNIC is required. Each student can apply only once.</p>
                    </div>

                    {{-- Photo upload — full row --}}
                    <div class="sm:col-span-2">
                        <label class="field-label" for="applicant_photo">Applicant photo <span class="text-red-500">*</span> <span class="text-slate-400 font-normal text-xs">(passport-size, JPG/PNG, max 2 MB)</span></label>
                        <div id="photo-drop-zone"
                             class="mt-1 flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-7 text-center cursor-pointer transition hover:border-blue-400 hover:bg-blue-50 @error('applicant_photo') border-red-400 bg-red-50 @enderror"
                             onclick="document.getElementById('applicant_photo').click()">
                            <div id="photo-preview-wrap" class="hidden">
                                <img id="photo-preview" src="#" alt="Preview"
                                     class="mx-auto h-24 w-24 rounded-full object-cover ring-4 ring-blue-200 mb-2">
                            </div>
                            <div id="photo-placeholder">
                                <span class="text-4xl">📷</span>
                                <p class="text-sm text-slate-500 mt-2 font-medium">Click to upload or drag &amp; drop</p>
                            </div>
                            <p id="photo-filename" class="text-xs text-slate-400 hidden"></p>
                        </div>
                        <input type="file" id="applicant_photo" name="applicant_photo"
                               accept="image/jpeg,image/png,image/jpg" class="hidden"
                               onchange="previewPhoto(this); clearPhotoError()">
                        @error('applicant_photo')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_applicant_photo">A passport-size photo is required.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Step 2: Guardian ────────────────────────────────── --}}
        <div class="step-panel" id="step-2">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-4">
                <div class="flex items-center gap-3.5 mb-6">
                    <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">👨‍👩‍👦</div>
                    <div>
                        <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Guardian Information</h2>
                        <p class="text-xs text-slate-500">Parent or guardian contact details</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="field-label" for="father_name">Father's name <span class="text-red-500">*</span></label>
                        <input id="father_name" name="father_name" required value="{{ old('father_name') }}"
                               placeholder="Full name"
                               class="field-input @error('father_name') ring-red-400 @enderror">
                        @error('father_name')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_father_name">Father's name is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="mother_name">Mother's name <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input id="mother_name" name="mother_name" value="{{ old('mother_name') }}"
                               placeholder="Full name"
                               class="field-input @error('mother_name') ring-red-400 @enderror">
                        @error('mother_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="parent_cnic">Parent / Guardian CNIC <span class="text-slate-400 font-normal">(optional, 13 digits)</span></label>
                        <input id="parent_cnic" name="parent_cnic"
                               inputmode="numeric"
                               pattern="\d{13}"
                               maxlength="13"
                               value="{{ old('parent_cnic') }}"
                               placeholder="e.g. 1234567890123"
                               class="field-input @error('parent_cnic') ring-red-400 @enderror">
                        @error('parent_cnic')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-400 mt-1">If you have multiple children applying, you may use the same Parent CNIC for each.</p>
                    </div>
                    <div>
                        <label class="field-label" for="guardian_phone">Guardian phone <span class="text-red-500">*</span></label>
                        <input id="guardian_phone" name="guardian_phone" type="tel" required value="{{ old('guardian_phone') }}"
                               placeholder="e.g. 03001234567"
                               class="field-input @error('guardian_phone') ring-red-400 @enderror">
                        @error('guardian_phone')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_guardian_phone">Guardian phone is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="guardian_email">Guardian email <span class="text-red-500">*</span></label>
                        <input type="email" id="guardian_email" name="guardian_email" required value="{{ old('guardian_email') }}"
                               placeholder="guardian@example.com"
                               class="field-input @error('guardian_email') ring-red-400 @enderror">
                        @error('guardian_email')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_guardian_email">A valid guardian email is required.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Step 3: Academics ───────────────────────────────── --}}
        <div class="step-panel" id="step-3">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-4">
                <div class="flex items-center gap-3.5 mb-6">
                    <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">🎓</div>
                    <div>
                        <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Academic Details</h2>
                        <p class="text-xs text-slate-500">Class, campus, and previous school</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">
                    <div>
                        <label class="field-label" for="academic_year_id">Academic year <span class="text-red-500">*</span></label>
                        <select id="academic_year_id" name="academic_year_id" required class="field-input @error('academic_year_id') ring-red-400 @enderror">
                            <option value="">Select year</option>
                            @foreach($years as $id => $name)
                                <option value="{{ $id }}" @selected(old('academic_year_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('academic_year_id')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_academic_year_id">Please select an academic year.</p>
                    </div>
                    <div>
                        <label class="field-label" for="class_id">Applying for class <span class="text-red-500">*</span></label>
                        <select id="class_id" name="class_id" required class="field-input @error('class_id') ring-red-400 @enderror">
                            <option value="">Select class</option>
                            @foreach($classes as $id => $name)
                                <option value="{{ $id }}" @selected(old('class_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_class_id">Please select a class.</p>
                    </div>
                    <div>
                        <label class="field-label" for="campus_id">Campus @if(count($campuses) > 1)<span class="text-red-500">*</span>@endif</label>
                        <select id="campus_id" name="campus_id" {{ count($campuses) > 1 ? 'required' : '' }} class="field-input @error('campus_id') ring-red-400 @enderror">
                            <option value="">Select campus</option>
                            @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" @selected(old('campus_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('campus_id')<p class="field-error">{{ $message }}</p>@enderror
                        @if(count($campuses) > 1)
                            <p class="field-error-msg" id="err_campus_id">Please select a campus.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="address">Home address <span class="text-red-500">*</span></label>
                        <input id="address" name="address" required value="{{ old('address') }}"
                               placeholder="Street, area"
                               class="field-input @error('address') ring-red-400 @enderror">
                        @error('address')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_address">Home address is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="city">City <span class="text-red-500">*</span></label>
                        <input id="city" name="city" required value="{{ old('city') }}"
                               placeholder="e.g. Lahore"
                               class="field-input @error('city') ring-red-400 @enderror">
                        @error('city')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_city">City is required.</p>
                    </div>
                    <div>
                        <label class="field-label" for="previous_school">Previous school <span class="text-red-500">*</span></label>
                        <input id="previous_school" name="previous_school" required value="{{ old('previous_school') }}"
                               placeholder="Name of last school attended"
                               class="field-input @error('previous_school') ring-red-400 @enderror">
                        @error('previous_school')<p class="field-error">{{ $message }}</p>@enderror
                        <p class="field-error-msg" id="err_previous_school">Previous school is required.</p>
                    </div>

                    {{-- Previous school marks — only shown for classes above Class 1.
                         JS toggles requiredness based on the selected class. --}}
                    <div id="prev_marks_wrap" class="sm:col-span-2 hidden">
                        <div class="rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-4 mb-3 text-xs text-amber-900 leading-relaxed">
                            📋 Previous school result is required for classes above Class 1. The school uses these marks alongside the entry test and interview to make admission decisions.
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="field-label" for="previous_school_score">Marks obtained <span id="prev_marks_required_star" class="text-red-500 hidden">*</span></label>
                                <input id="previous_school_score" name="previous_school_score"
                                       type="number" step="0.01" min="0"
                                       value="{{ old('previous_school_score') }}"
                                       placeholder="e.g. 410"
                                       class="field-input @error('previous_school_score') ring-red-400 @enderror">
                                @error('previous_school_score')<p class="field-error">{{ $message }}</p>@enderror
                                <p class="field-error-msg" id="err_previous_school_score">Marks obtained is required.</p>
                            </div>
                            <div>
                                <label class="field-label" for="previous_score_max">Out of total marks <span id="prev_max_required_star" class="text-red-500 hidden">*</span></label>
                                <input id="previous_score_max" name="previous_score_max"
                                       type="number" step="0.01" min="1"
                                       value="{{ old('previous_score_max') }}"
                                       placeholder="e.g. 500"
                                       class="field-input @error('previous_score_max') ring-red-400 @enderror">
                                @error('previous_score_max')<p class="field-error">{{ $message }}</p>@enderror
                                <p class="field-error-msg" id="err_previous_score_max">Total marks is required.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Step 4: Review & Submit ─────────────────────────── --}}
        <div class="step-panel" id="step-4">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-7 mb-4">
                <div class="flex items-center gap-3.5 mb-6">
                    <div class="w-11 h-11 rounded-2xl grad-brand flex items-center justify-center text-xl shadow-lg shadow-blue-500/25">✅</div>
                    <div>
                        <h2 class="font-extrabold tracking-tight text-slate-900 text-lg">Review &amp; Submit</h2>
                        <p class="text-xs text-slate-500">Confirm your details before submitting</p>
                    </div>
                </div>

                {{-- Summary card --}}
                <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 divide-y divide-slate-200 mb-5 text-sm overflow-hidden" id="reviewSummary">
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Name</span>
                        <span class="font-medium text-slate-900" id="rev_name">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Date of birth</span>
                        <span class="font-medium text-slate-900" id="rev_dob">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Gender</span>
                        <span class="font-medium text-slate-900" id="rev_gender">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Student email</span>
                        <span class="font-medium text-slate-900" id="rev_email">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Guardian phone</span>
                        <span class="font-medium text-slate-900" id="rev_gphone">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Class applied for</span>
                        <span class="font-medium text-slate-900" id="rev_class">—</span>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-x-4">
                        <span class="text-slate-500">Campus</span>
                        <span class="font-medium text-slate-900" id="rev_campus">—</span>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="field-label" for="notes">Anything else we should know? <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Special requirements, health notes, or any additional information…"
                              class="field-input resize-none @error('notes') ring-red-400 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-2xl bg-blue-50 ring-1 ring-blue-200 p-4 text-xs sm:text-sm text-blue-800 mb-5 leading-relaxed">
                    📬 After submitting you will receive a <strong>unique tracking link</strong> to monitor your application status. Save it — no account is required.
                </div>

                <button type="submit" id="submitBtn"
                    class="btn-grad w-full rounded-xl text-white font-semibold py-3.5 min-h-[52px] text-base flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <span id="submitLabel">Submit Application</span>
                    <svg id="submitSpinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ─── Navigation buttons ───────────────────────────────── --}}
        <div class="flex gap-3 mt-2">
            <button type="button" id="btnBack"
                class="hidden flex-1 sm:flex-none sm:w-36 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold py-3 min-h-[48px] text-base transition-colors">
                ← Back
            </button>
            <button type="button" id="btnNext"
                class="btn-grad flex-1 rounded-xl text-white font-semibold py-3 min-h-[48px] text-base">
                Next →
            </button>
        </div>

    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>

<script>
(function () {
    const TOTAL = 4;
    let current = 1;

    // Fields that belong to each step (for error-step detection)
    const stepFields = {
        1: ['first_name','last_name','date_of_birth','gender','phone','email'],
        2: ['father_name','mother_name','guardian_phone','guardian_email'],
        3: ['academic_year_id','class_id','campus_id','address','city','previous_school'],
        4: ['notes'],
    };

    const errorFields = @json(array_keys($errors->toArray()));

    // Detect which step has the first error and start there
    if (errorFields.length > 0) {
        for (let s = 1; s <= TOTAL; s++) {
            if (stepFields[s].some(f => errorFields.includes(f))) {
                current = s;
                break;
            }
        }
    }

    function updateUI() {
        for (let s = 1; s <= TOTAL; s++) {
            const panel  = document.getElementById('step-' + s);
            const circle = document.getElementById('sc-' + s);
            const label  = document.getElementById('sl-' + s);

            panel.classList.toggle('active', s === current);

            if (s < current) {
                circle.className = 'step-circle bg-blue-600 text-white';
                circle.textContent = '✓';
                label.className = 'text-xs mt-1 font-medium text-blue-600';
            } else if (s === current) {
                circle.className = 'step-circle bg-blue-600 text-white shadow-md shadow-blue-200';
                circle.textContent = s;
                label.className = 'text-xs mt-1 font-bold text-blue-700';
            } else {
                circle.className = 'step-circle bg-slate-100 text-slate-400';
                circle.textContent = s;
                label.className = 'text-xs mt-1 font-medium text-slate-400';
            }

            const line = document.getElementById('line-' + s);
            if (line) line.classList.toggle('done', s < current);
        }

        document.getElementById('btnBack').classList.toggle('hidden', current === 1);
        const btnNext = document.getElementById('btnNext');
        if (current === TOTAL) {
            btnNext.classList.add('hidden');
        } else {
            btnNext.classList.remove('hidden');
        }
    }

    function val(name) {
        const el = document.querySelector('[name="' + name + '"]');
        return el ? el.value.trim() : '';
    }

    function selectedText(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el || !el.selectedIndex) return '—';
        return el.options[el.selectedIndex]?.text || '—';
    }

    function populateReview() {
        const fn = val('first_name'), ln = val('last_name');
        document.getElementById('rev_name').textContent   = (fn + ' ' + ln).trim() || '—';
        document.getElementById('rev_dob').textContent    = val('date_of_birth') || '—';
        document.getElementById('rev_gender').textContent = val('gender') || '—';
        document.getElementById('rev_email').textContent  = val('email') || '—';
        document.getElementById('rev_gphone').textContent = val('guardian_phone') || '—';
        document.getElementById('rev_class').textContent  = selectedText('class_id');
        document.getElementById('rev_campus').textContent = selectedText('campus_id');
    }

    const stepRequired = {
        1: ['first_name','last_name','date_of_birth','gender','phone','email','student_cnic'],
        2: ['father_name','guardian_phone','guardian_email'],
        3: ['academic_year_id','class_id','address','city','previous_school'],
        4: [],
    };

    // class_id → sort_order map (set by the controller). Anything with
    // sort_order > 1 is "above Class 1" and needs previous-school marks.
    const CLASS_SORT_ORDER = @json($classSortOrder ?? new \stdClass);

    function selectedClassRequiresPrevMarks() {
        const sel = document.getElementById('class_id');
        if (!sel || !sel.value) return false;
        const so = CLASS_SORT_ORDER[sel.value];
        return typeof so === 'number' && so > 1;
    }

    function togglePrevMarksUi() {
        const wrap = document.getElementById('prev_marks_wrap');
        if (!wrap) return;
        const requireIt = selectedClassRequiresPrevMarks();
        wrap.classList.toggle('hidden', !document.getElementById('class_id').value);
        document.getElementById('prev_marks_required_star').classList.toggle('hidden', !requireIt);
        document.getElementById('prev_max_required_star').classList.toggle('hidden', !requireIt);

        // Manage step-3 required list
        const need3 = stepRequired[3];
        ['previous_school_score','previous_score_max'].forEach(name => {
            const i = need3.indexOf(name);
            if (requireIt) {
                if (i === -1) need3.push(name);
            } else {
                if (i !== -1) need3.splice(i, 1);
            }
        });
    }

    document.getElementById('class_id')?.addEventListener('change', togglePrevMarksUi);
    togglePrevMarksUi();

    // CNIC: live-validate to 13 numeric digits before allowing step advance.
    function isValidCnic(v) {
        return /^\d{13}$/.test((v || '').trim());
    }

    // Photo is validated separately (file input, not a text value).
    const photoInput = document.getElementById('applicant_photo');
    const photoDropZone = document.getElementById('photo-drop-zone');
    const photoErrMsg = document.getElementById('err_applicant_photo');

    function showPhotoError() {
        photoDropZone.classList.add('border-red-400','bg-red-50');
        photoDropZone.classList.remove('border-slate-200','bg-slate-50');
        if (photoErrMsg) photoErrMsg.classList.add('visible');
        photoDropZone.classList.remove('shake');
        void photoDropZone.offsetWidth;
        photoDropZone.classList.add('shake');
    }

    window.clearPhotoError = function () {
        photoDropZone.classList.remove('border-red-400','bg-red-50');
        photoDropZone.classList.add('border-slate-200','bg-slate-50');
        if (photoErrMsg) photoErrMsg.classList.remove('visible');
    };

    // campus only required when there is more than one option
    const campusSelect = document.getElementById('campus_id');
    if (campusSelect && campusSelect.options.length > 2) {
        stepRequired[3].push('campus_id');
    }

    function markInvalid(el) {
        el.classList.add('invalid');
        const errEl = document.getElementById('err_' + el.name);
        if (errEl) errEl.classList.add('visible');
        // shake the parent wrapper
        const wrap = el.closest('div');
        if (wrap) {
            wrap.classList.remove('shake');
            void wrap.offsetWidth; // reflow
            wrap.classList.add('shake');
        }
    }

    function markValid(el) {
        el.classList.remove('invalid');
        const errEl = document.getElementById('err_' + el.name);
        if (errEl) errEl.classList.remove('visible');
    }

    // Clear invalid on user input
    document.querySelectorAll('.field-input').forEach(el => {
        el.addEventListener('input', () => markValid(el));
        el.addEventListener('change', () => markValid(el));
    });

    function validateStep(step) {
        let ok = true;
        let firstBad = null;
        (stepRequired[step] || []).forEach(name => {
            const el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            if (!el.value.trim()) {
                markInvalid(el);
                if (!firstBad) firstBad = el;
                ok = false;
            } else {
                markValid(el);
            }
        });

        // Step 1: also require photo + valid 13-digit student CNIC format.
        if (step === 1 && photoInput && (!photoInput.files || photoInput.files.length === 0)) {
            showPhotoError();
            if (!firstBad) photoDropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            ok = false;
        }
        if (step === 1) {
            const cnicEl = document.getElementById('student_cnic');
            if (cnicEl && cnicEl.value && !isValidCnic(cnicEl.value)) {
                markInvalid(cnicEl);
                if (!firstBad) firstBad = cnicEl;
                ok = false;
            }
        }

        // Step 2: parent_cnic optional, but if filled must be 13 digits.
        if (step === 2) {
            const pEl = document.getElementById('parent_cnic');
            if (pEl && pEl.value && !isValidCnic(pEl.value)) {
                markInvalid(pEl);
                if (!firstBad) firstBad = pEl;
                ok = false;
            }
        }

        if (firstBad) firstBad.focus();
        return ok;
    }

    document.getElementById('btnNext').addEventListener('click', function () {
        if (!validateStep(current)) return;
        if (current < TOTAL) {
            current++;
            if (current === TOTAL) populateReview();
            updateUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    document.getElementById('btnBack').addEventListener('click', function () {
        if (current > 1) {
            current--;
            updateUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Spinner on submit
    document.getElementById('admissionForm').addEventListener('submit', function () {
        document.getElementById('submitLabel').textContent = 'Submitting…';
        document.getElementById('submitSpinner').classList.remove('hidden');
        document.getElementById('submitBtn').disabled = true;
    });

    updateUI();
})();

function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    const previewWrap = document.getElementById('photo-preview-wrap');
    const placeholder = document.getElementById('photo-placeholder');
    const filename = document.getElementById('photo-filename');
    const dropZone = document.getElementById('photo-drop-zone');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            previewWrap.classList.remove('hidden');
            placeholder.classList.add('hidden');
            filename.textContent = file.name;
            filename.classList.remove('hidden');
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
            dropZone.classList.remove('border-slate-200', 'bg-slate-50');
        };
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>
