<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admissions — {{ $tenant->school_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        @if ($settings->logo_path)
            <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="" class="mx-auto h-16 mb-4">
        @endif

        <div class="inline-flex items-center gap-2 bg-amber-100 text-amber-900 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
            ⏳ Closed
        </div>

        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $tenant->school_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Online admissions</p>

        <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 p-5">
            <h2 class="text-lg font-semibold text-amber-900">Admission is not open</h2>
            <p class="mt-2 text-sm text-amber-800">
                We are not currently accepting online applications. Please check back later when the next admission cycle is announced.
            </p>
        </div>

        @if ($settings->phone || $settings->email)
            <div class="mt-6 text-sm text-slate-600">
                <div class="font-semibold text-slate-700">Need more information?</div>
                <div class="mt-1">
                    @if ($settings->phone)
                        ☎ <a href="tel:{{ $settings->phone }}" class="text-blue-600 hover:underline">{{ $settings->phone }}</a>
                    @endif
                    @if ($settings->email)
                        @if ($settings->phone) · @endif
                        ✉ <a href="mailto:{{ $settings->email }}" class="text-blue-600 hover:underline">{{ $settings->email }}</a>
                    @endif
                </div>
            </div>
        @endif

        <a href="javascript:history.back()" class="mt-6 inline-block text-sm text-slate-500 hover:text-slate-700">← Back to website</a>
    </div>
</body>
</html>
