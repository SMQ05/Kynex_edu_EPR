<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e1b4b">
    <title>Admissions — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-900 relative overflow-hidden"
      style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.45), transparent 60%), radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%);"></div>

    <div class="relative max-w-md w-full bg-white rounded-3xl shadow-2xl shadow-slate-900/30 p-8 text-center">
        @if ($settings->logo_path)
            <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="" class="mx-auto h-16 mb-4">
        @else
            <div class="mx-auto mb-4 w-14 h-14 rounded-2xl flex items-center justify-center text-white font-black text-2xl"
                 style="background: linear-gradient(135deg,#4f46e5,#2563eb 55%,#06b6d4);">
                {{ mb_substr($tenant->school_name, 0, 1) }}
            </div>
        @endif

        <div class="inline-flex items-center gap-2 bg-amber-100 text-amber-900 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider">
            ⏳ Closed
        </div>

        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">{{ $tenant->school_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Online admissions</p>

        <div class="mt-6 rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-5">
            <h2 class="text-lg font-bold text-amber-900">Admission is not open</h2>
            <p class="mt-2 text-sm text-amber-800 leading-relaxed">
                We are not currently accepting online applications. Please check back later when the next admission cycle is announced.
            </p>
        </div>

        @if ($settings->phone || $settings->email)
            <div class="mt-6 text-sm text-slate-600">
                <div class="font-semibold text-slate-700">Need more information?</div>
                <div class="mt-2 flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
                    @if ($settings->phone)
                        <a href="tel:{{ $settings->phone }}" class="inline-flex items-center gap-1.5 min-h-[44px] text-blue-600 hover:underline font-medium">☎ {{ $settings->phone }}</a>
                    @endif
                    @if ($settings->phone && $settings->email) <span class="text-slate-300">·</span> @endif
                    @if ($settings->email)
                        <a href="mailto:{{ $settings->email }}" class="inline-flex items-center gap-1.5 min-h-[44px] text-blue-600 hover:underline font-medium">✉ {{ $settings->email }}</a>
                    @endif
                </div>
            </div>
        @endif

        <a href="javascript:history.back()" class="mt-6 inline-flex items-center justify-center min-h-[44px] text-sm font-medium text-slate-500 hover:text-slate-700">← Back to website</a>
    </div>
</body>
</html>
