<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification | {{ $schoolName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
            padding: 36px 32px;
            max-width: 540px;
            width: 100%;
        }
        .school {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #6b7280;
            text-align: center;
        }
        .school strong { color: #1e3a8a; font-weight: 700; }
        .badge {
            display: inline-block;
            margin: 12px 0;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .badge.ok    { background: #dcfce7; color: #166534; }
        .badge.fail  { background: #fee2e2; color: #991b1b; }
        h1 {
            font-size: 22px;
            margin: 6px 0 18px;
            text-align: center;
            color: #111827;
        }
        .statement {
            font-size: 16px;
            line-height: 1.7;
            color: #1f2937;
            text-align: center;
            margin-bottom: 24px;
        }
        .statement strong { color: #111827; }
        .meta {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 13px;
            color: #4b5563;
        }
        .meta dt { font-weight: 600; color: #6b7280; margin-top: 8px; }
        .meta dt:first-child { margin-top: 0; }
        .meta dd { color: #111827; margin-bottom: 4px; }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
        .footer a { color: #6b7280; text-decoration: none; }
        .icon { text-align: center; margin-bottom: 6px; font-size: 36px; line-height: 1; }
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
            <div class="icon" style="color:#b91c1c;" aria-hidden="true">&#10005;</div>
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
