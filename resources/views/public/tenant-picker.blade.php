<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Choose your school — KynexEdu</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-md mx-auto py-12 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-xl font-bold mb-2">Choose your school</h1>
        <p class="text-sm text-slate-600 mb-6">Pick the school you're applying to (or signing up as a parent for).</p>

        <div class="space-y-2">
            @forelse($tenants as $t)
                <a href="{{ route('public.' . $next, ['tenant' => $t->id]) }}"
                   class="block rounded-lg ring-1 ring-slate-200 hover:bg-slate-50 px-4 py-3 text-sm">
                    <div class="font-semibold">{{ $t->school_name }}</div>
                    <div class="text-xs text-slate-500">{{ $t->id }}</div>
                </a>
            @empty
                <p class="text-sm text-slate-500">No schools registered yet.</p>
            @endforelse
        </div>
    </div>
</div>
</body>
</html>
