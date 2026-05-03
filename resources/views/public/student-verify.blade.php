<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Verification — {{ $schoolName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #1f2937; min-height: 100vh; display: flex;
            align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15); padding: 36px 32px;
            max-width: 480px; width: 100%; }
        .school-banner { text-align: center; margin-bottom: 18px; }
        .school-banner img { max-height: 50px; }
        .school-banner .name { font-size: 12px; text-transform: uppercase;
            letter-spacing: 2px; color: #475569; margin-top: 6px; }
        .school-banner .name strong { color: #1e3a8a; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px;
            font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
        .ok    { background: #dcfce7; color: #166534; }
        .nope  { background: #fee2e2; color: #991b1b; }
        .gray  { background: #e5e7eb; color: #374151; }
        h1 { font-size: 22px; text-align: center; color: #111827; margin: 6px 0 16px; }
        .photo { width: 130px; height: 160px; margin: 0 auto 12px;
            border: 3px solid #1e3a8a; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            color: #cbd5e1; font-size: 11px; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .student-name { text-align: center; font-size: 22px; font-weight: 700; color: #1e3a8a; }
        .meta { font-size: 13px; line-height: 1.6; }
        .meta dt { font-weight: 600; color: #64748b;
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
        .meta dt:first-child { margin-top: 0; }
        .meta dd { color: #111827; }
        .stmt { background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px 14px;
            border-radius: 8px; margin: 18px 0; font-size: 13px; color: #1e3a8a;
            text-align: center; line-height: 1.6; }
        .footer { margin-top: 22px; text-align: center; font-size: 11px; color: #94a3b8; }
        .footer a { color: #64748b; text-decoration: none; }
        .icon { text-align: center; font-size: 36px; line-height: 1; margin-bottom: 6px; }
        .icon.ok { color: #16a34a; }
        .icon.fail { color: #dc2626; }
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
