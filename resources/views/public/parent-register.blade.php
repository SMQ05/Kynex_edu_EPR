<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Parent Sign-up — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-md mx-auto py-12 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-2xl font-bold mb-1">Parent Sign-up</h1>
        <p class="text-sm text-slate-600 mb-6">{{ $tenant->school_name }}</p>

        @if($errors->any())
            <div class="mb-4 p-4 rounded-lg bg-red-50 ring-1 ring-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <p class="text-sm text-slate-600 mb-4">
            To sign up, enter your child's admission or registration number and the email you provided as their guardian.
        </p>

        <form method="POST" action="{{ route('public.parent.register.submit') }}" class="space-y-4">
            @csrf
            <label class="block">
                <span class="text-sm font-medium">Student admission or registration number</span>
                <input name="student_reference" required value="{{ old('student_reference') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
            </label>
            <label class="block">
                <span class="text-sm font-medium">Your email (must match guardian record)</span>
                <input type="email" name="guardian_email" required value="{{ old('guardian_email') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
            </label>
            <button type="submit" class="w-full rounded-lg bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-3">
                Send Activation Link
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-500">
            Already have an account? <a href="{{ url('/parent/login') }}" class="text-indigo-700 underline">Login here</a>.
        </p>
    </div>
</div>
</body>
</html>
