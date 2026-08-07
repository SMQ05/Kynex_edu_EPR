<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e1b4b">
    <title>Certificate Verification | {{ $schoolName }}</title>
<style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            background: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: ''; position: absolute; inset: 0; z-index: 0; opacity: .6;
            background:
              radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.45), transparent 60%),
              radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%);
        }
        .card {
            position: relative; z-index: 1;
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 30px 60px -12px rgba(15,23,42,.4);
            padding: 40px 32px;
            max-width: 540px;
            width: 100%;
        }
        .school {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            color: #64748b;
            text-align: center;
        }
        .school strong { color: #1e3a8a; font-weight: 800; }
        .badge {
            display: inline-block;
            margin: 12px 0;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .badge.ok    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge.fail  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 6px 0 18px;
            text-align: center;
            color: #0f172a;
        }
        .statement {
            font-size: 16px;
            line-height: 1.7;
            color: #334155;
            text-align: center;
            margin-bottom: 24px;
        }
        .statement strong { color: #0f172a; }
        .meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 20px;
            font-size: 13px;
            color: #475569;
        }
        .meta dt { font-weight: 600; color: #64748b; margin-top: 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .meta dt:first-child { margin-top: 0; }
        .meta dd { color: #0f172a; margin-bottom: 4px; font-weight: 500; }
        .footer {
            margin-top: 26px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
        .footer a { color: #64748b; text-decoration: none; font-weight: 600; }
        .icon {
            text-align: center; margin: 0 auto 12px; font-size: 30px; line-height: 1;
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            background: #ecfdf5; color: #16a34a; border: 1px solid #bbf7d0;
        }
        .icon.fail { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    </style>
</head>
<body>
    <div class="card">
        @if ($certificate)
            <div class="icon" aria-hidden="true">&#10003;</div>
            <div class="school">Issued by <strong>{{ $schoolName }}</strong></div>
            <div style="text-align:center;"><span class="badge ok">Verified</span></div>
            <h1>Certificate Verified</h1>

            <p class="statement">
                <strong>{{ $schoolName }}</strong> verifies that
                <strong>{{ $certificate->template?->name ?: 'this certificate' }}</strong>
                has been issued to
                <strong>{{ $certificate->student?->full_name ?: '—' }}</strong>
                @if ($certificate->student?->registration_number)
                    (Registration #<strong>{{ $certificate->student->registration_number }}</strong>)
                @endif
                on <strong>{{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}</strong>.
            </p>

            <dl class="meta">
                <dt>Certificate Number</dt>
                <dd>{{ $certificate->certificate_number }}</dd>

                <dt>Type</dt>
                <dd>{{ $certificate->template?->name ?: '—' }}</dd>

                <dt>Issued On</dt>
                <dd>{{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}</dd>

                <dt>Issued To</dt>
                <dd>{{ $certificate->student?->full_name ?: '—' }}</dd>

                @if ($certificate->student?->registration_number)
                    <dt>Registration Number</dt>
                    <dd>{{ $certificate->student->registration_number }}</dd>
                @endif
            </dl>
        @else
            <div class="icon fail" aria-hidden="true">&#10005;</div>
            <div class="school">Verification request for <strong>{{ $schoolName }}</strong></div>
            <div style="text-align:center;"><span class="badge fail">Not Found</span></div>
            <h1>Certificate Not Found</h1>
            <p class="statement">
                We could not find a certificate with number
                <strong>{{ $requestedNumber }}</strong>
                issued by <strong>{{ $schoolName }}</strong>.
            </p>
            <dl class="meta">
                <dt>Requested Number</dt>
                <dd>{{ $requestedNumber }}</dd>
                <dt>Status</dt>
                <dd>Not on record</dd>
            </dl>
        @endif

        <div class="footer">
            Powered by KynexEdu &middot; <a href="https://kynexsolutions.com">kynexsolutions.com</a>
        </div>
    </div>
</body>
</html>
