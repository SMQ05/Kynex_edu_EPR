<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Apply — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-3xl mx-auto py-12 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-2xl font-bold mb-1">Student Admission Application</h1>
        <p class="text-sm text-slate-600 mb-6">{{ $tenant->school_name }} — fill out the form below to apply for admission. After submission you will receive a tracking link to follow your status.</p>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-emerald-50 ring-1 ring-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 ring-1 ring-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- Submit to the current URL so the form works whether the
             applicant is on /apply (tenant subdomain) or
             /site/{tenant}/apply (central domain). --}}
        <form method="POST" action="" class="space-y-6">
            @csrf
            <fieldset>
                <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Applicant</legend>
                <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium">First name *</span>
                        <input name="first_name" required value="{{ old('first_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Last name *</span>
                        <input name="last_name" required value="{{ old('last_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Date of birth</span>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Gender</span>
                        <select name="gender" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">--</option>
                            <option value="male" @selected(old('gender')==='male')>Male</option>
                            <option value="female" @selected(old('gender')==='female')>Female</option>
                            <option value="other" @selected(old('gender')==='other')>Other</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Phone</span>
                        <input name="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block col-span-2">
                        <span class="text-sm font-medium">Address</span>
                        <input name="address" value="{{ old('address') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">City</span>
                        <input name="city" value="{{ old('city') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Previous school</span>
                        <input name="previous_school" value="{{ old('previous_school') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Guardian</legend>
                <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium">Father's name</span>
                        <input name="father_name" value="{{ old('father_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Mother's name</span>
                        <input name="mother_name" value="{{ old('mother_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Guardian phone</span>
                        <input name="guardian_phone" value="{{ old('guardian_phone') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Guardian email</span>
                        <input type="email" name="guardian_email" value="{{ old('guardian_email') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Class &amp; Campus</legend>
                <div class="grid grid-cols-3 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium">Academic year</span>
                        <select name="academic_year_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">--</option>
                            @foreach($years as $id => $name)
                                <option value="{{ $id }}" @selected(old('academic_year_id')===$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Class</span>
                        <select name="class_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">--</option>
                            @foreach($classes as $id => $name)
                                <option value="{{ $id }}" @selected(old('class_id')===$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Campus</span>
                        <select name="campus_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">--</option>
                            @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" @selected(old('campus_id')===$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium">Anything else we should know?</span>
                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">{{ old('notes') }}</textarea>
            </label>

            <button type="submit" class="w-full rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3">
                Submit Application
            </button>
        </form>
    </div>
</div>
</body>
</html>
