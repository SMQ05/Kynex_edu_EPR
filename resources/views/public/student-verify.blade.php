<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e1b4b">
    <title>Student Verification — {{ $schoolName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
            color: #1f2937; min-height: 100vh; display: flex;
            align-items: center; justify-content: center; padding: 24px 20px;
            position: relative; overflow: hidden; }
        body::before {
            content: ''; position: absolute; inset: 0; z-index: 0; opacity: .6;
            background:
              radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.45), transparent 60%),
              radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%); }
        .card { position: relative; z-index: 1; background: #fff; border-radius: 28px;
            box-shadow: 0 30px 60px -12px rgba(15,23,42,.45); padding: 40px 32px;
            max-width: 480px; width: 100%; }
        .school-banner { text-align: center; margin-bottom: 20px; }
        .school-banner img { max-height: 52px; margin: 0 auto; }
        .school-banner .name { font-size: 12px; text-transform: uppercase;
            letter-spacing: 1.5px; font-weight: 600; color: #64748b; margin-top: 8px; }
        .school-banner .name strong { color: #1e3a8a; font-weight: 800; }
        .badge { display: inline-block; padding: 5px 14px; border-radius: 999px;
            font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .ok    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .nope  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .gray  { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        h1 { font-size: 24px; font-weight: 800; letter-spacing: -.02em; text-align: center; color: #0f172a; margin: 8px 0 16px; }
        .photo { width: 130px; height: 160px; margin: 0 auto 14px;
            border-radius: 16px; overflow: hidden;
            border: 3px solid #2563eb; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8; font-size: 11px; box-shadow: 0 10px 24px -8px rgba(37,99,235,.4); }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .student-name { text-align: center; font-size: 23px; font-weight: 800; letter-spacing: -.01em; color: #1e3a8a; }
        .meta { font-size: 13px; line-height: 1.6; }
        .meta dt { font-weight: 600; color: #64748b;
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
        .meta dt:first-child { margin-top: 0; }
        .meta dd { color: #0f172a; font-weight: 500; }
        .stmt { background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px 16px;
            border-radius: 14px; margin: 18px 0; font-size: 13px; color: #1e3a8a;
            text-align: center; line-height: 1.6; }
        .footer { margin-top: 24px; text-align: center; font-size: 11px; color: #94a3b8; }
        .footer a { color: #64748b; text-decoration: none; font-weight: 600; }
        .icon { margin: 0 auto 10px; font-size: 30px; line-height: 1;
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center; }
        .icon.ok { color: #16a34a; background: #ecfdf5; border: 1px solid #bbf7d0; }
        .icon.fail { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="card">
        <div class="school-banner">
            @if ($logoPath)
                <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $schoolName }}">
            @endif
            <div class="name">Issued by <strong>{{ $schoolName }}</strong></div>
        </div>

        @if ($student)
            <div class="icon ok">&#10003;</div>
            <div style="text-align: center;"><span class="badge ok">Valid Student</span></div>
            <h1>Card Verified</h1>

            <div class="photo">
                @if ($student->profile_photo_path)
                    <img src="{{ asset('storage/' . $student->profile_photo_path) }}" alt="">
                @else
                    No photo
                @endif
            </div>

            <div class="student-name">{{ $student->full_name }}</div>

            <div class="stmt">
                <strong>{{ $schoolName }}</strong> confirms that this student is currently enrolled.
            </div>

            <dl class="meta">
                <dt>Admission Number</dt>
                <dd>{{ $student->admission_number ?? '—' }}</dd>
                @if ($student->registration_number)
                    <dt>Registration Number</dt>
                    <dd>{{ $student->registration_number }}</dd>
                @endif
                <dt>Class</dt>
                <dd>{{ $student->schoolClass?->name ?? '—' }}{{ $student->section?->name ? ' · ' . $student->section->name : '' }}</dd>
                @if ($student->campus?->name)
                    <dt>Campus</dt>
                    <dd>{{ $student->campus->name }}</dd>
                @endif
                @if ($student->academicYear?->name)
                    <dt>Academic Year</dt>
                    <dd>{{ $student->academicYear->name }}</dd>
                @endif
                <dt>Status</dt>
                <dd>
                    @php
                        $status = $student->status instanceof \BackedEnum ? $student->status->value : (string) $student->status;
                    @endphp
                    @if ($status === 'enrolled')
                        <span class="badge ok">Enrolled</span>
                    @else
                        <span class="badge gray">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                    @endif
                </dd>
            </dl>
        @else
            <div class="icon fail">&#10005;</div>
            <div style="text-align: center;"><span class="badge nope">Not Found</span></div>
            <h1>Card Not Recognised</h1>
            <p style="text-align: center; font-size: 13px; color: #4b5563;">
                We could not find a student with reference
                <strong>{{ $identifier }}</strong> at <strong>{{ $schoolName }}</strong>.
                If this card was issued to you, please contact the school office.
            </p>
        @endif

        <div class="footer">
            Powered by <a href="https://kynexsolutions.com">kynexsolutions.com</a>
        </div>
    </div>
</body>
</html>
