{{--
    Public QR verification result — reached by scanning the code on a printed
    student ID card or certificate. No login, no chrome, no navigation: the
    only job is to tell whoever scanned it whether the document is genuine.

    Styling is self-contained because this page is served outside any Filament
    panel and must not depend on a compiled app stylesheet.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}{{ $school ? ' · ' . $school : '' }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center;
            justify-content: center; padding: 1.5rem;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6; color: #111827;
        }
        .card {
            width: 100%; max-width: 27rem; background: #fff; border-radius: 1rem;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,.25); overflow: hidden;
        }
        .banner { padding: 1.5rem; text-align: center; color: #fff; }
        .banner--ok  { background: linear-gradient(135deg, #0f3d2e, #0d9488); }
        .banner--bad { background: linear-gradient(135deg, #7f1d1d, #dc2626); }
        .mark { font-size: 2.5rem; line-height: 1; }
        .banner h1 { margin: .5rem 0 0; font-size: 1.125rem; font-weight: 700; }
        .banner p  { margin: .25rem 0 0; font-size: .8125rem; opacity: .85; }
        .body { padding: 1.25rem 1.5rem 1.5rem; }
        .subject { font-size: 1.25rem; font-weight: 700; margin: 0 0 1rem; }
        dl { margin: 0; }
        .row { display: flex; justify-content: space-between; gap: 1rem;
               padding: .625rem 0; border-bottom: 1px solid #f3f4f6; }
        .row:last-child { border-bottom: 0; }
        dt { font-size: .8125rem; color: #6b7280; margin: 0; }
        dd { font-size: .8125rem; font-weight: 600; margin: 0; text-align: right; }
        .msg { font-size: .875rem; color: #4b5563; line-height: 1.5; margin: 0; }
        .foot { padding: .875rem 1.5rem; background: #f9fafb; font-size: .6875rem;
                color: #6b7280; text-align: center; }
        @media (prefers-color-scheme: dark) {
            body { background: #111827; color: #f9fafb; }
            .card { background: #1f2937; box-shadow: 0 10px 30px -10px rgba(0,0,0,.6); }
            .row { border-bottom-color: #374151; }
            dt, .msg { color: #9ca3af; }
            .foot { background: #111827; color: #6b7280; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="banner {{ $valid ? 'banner--ok' : 'banner--bad' }}">
            <div class="mark" aria-hidden="true">{{ $valid ? '✓' : '✕' }}</div>
            <h1>{{ $title }}</h1>
            @if ($school)
                <p>Issued by {{ $school }}</p>
            @endif
        </div>

        <div class="body">
            @if ($valid)
                <p class="subject">{{ $subject }}</p>
                <dl>
                    @foreach ($rows as $label => $value)
                        <div class="row">
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="msg">{{ $message }}</p>
            @endif
        </div>

        <div class="foot">
            Checked {{ now()->format('M j, Y \a\t g:i A') }} — this page reflects the school's records at the time of scanning.
        </div>
    </main>
</body>
</html>
