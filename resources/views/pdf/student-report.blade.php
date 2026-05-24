@php
    use Illuminate\Support\Str;
    $heading = $data['heading'] ?? '';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .school { font-size: 13px; font-weight: bold; }
        .meta { color: #666; font-size: 9px; margin: 2px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1a1a2e; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
        tr:nth-child(even) td { background: #f8f8f8; }
        .c { text-align: center; }
        .b { font-weight: bold; }
        .footer { margin-top: 16px; text-align: center; color: #999; font-size: 8px; }
        .kv { margin-top: 8px; }
        .kv td { border: none; padding: 2px 6px; }
        .kv .lbl { color: #888; width: 130px; }
        h3 { font-size: 12px; margin: 14px 0 2px; }
    </style>
</head>
<body>
    <div class="school">{{ $tenant?->name ?? 'School Report' }}</div>
    <h1>{{ $title }}</h1>
    @if ($heading)<div class="meta">{{ $heading }}</div>@endif
    <div class="meta">Generated: {{ now()->format('d M Y, h:i A') }}</div>

    {{-- Subject Attendance --}}
    @if ($reportType === 'subject_attendance' && ! empty($data['rows']))
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th>Subject</th><th class="c">Total</th><th class="c">Present</th><th class="c">Absent</th><th class="c">Late</th><th class="c">%</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll'] ?? '—' }}</td><td>{{ $row['student'] }}</td><td>{{ $row['subject'] }}</td>
                        <td class="c">{{ $row['total'] }}</td><td class="c">{{ $row['present'] }}</td>
                        <td class="c">{{ $row['absent'] }}</td><td class="c">{{ $row['late'] }}</td><td class="c b">{{ $row['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Homework Evaluation --}}
    @elseif ($reportType === 'homework_eval' && ! empty($data['rows']))
        <p>Submissions: <b>{{ $data['stats']['total'] }}</b> · Graded: <b>{{ $data['stats']['graded'] }}</b>
           · Pending: <b>{{ $data['stats']['pending'] }}</b>
           · Avg: <b>{{ $data['stats']['avg_pct'] !== null ? $data['stats']['avg_pct'].'%' : '—' }}</b></p>
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th>Homework</th><th>Subject</th><th>Submitted</th><th class="c">Marks</th><th class="c">Grade</th><th class="c">%</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll'] ?? '—' }}</td><td>{{ $row['student'] }}</td>
                        <td>{{ $row['homework'] }}</td><td>{{ $row['subject'] }}</td>
                        <td>{{ $row['submitted_at'] ?? '—' }}</td>
                        <td class="c">{{ $row['marks'] !== null ? $row['marks'].'/'.($row['total_marks'] ?? '?') : '—' }}</td>
                        <td class="c">{{ $row['grade'] ?? ($row['graded'] ? '—' : 'Pending') }}</td>
                        <td class="c">{{ $row['percentage'] !== null ? $row['percentage'].'%' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Student History --}}
    @elseif ($reportType === 'student_history' && ! empty($data['student']))
        @php $s = $data['student']; @endphp
        <table class="kv">
            <tr><td class="lbl">Name</td><td class="b">{{ $s->full_name }}</td><td class="lbl">Admission No</td><td>{{ $s->admission_number ?? '—' }}</td></tr>
            <tr><td class="lbl">Roll No</td><td>{{ $s->roll_number ?? '—' }}</td><td class="lbl">Class / Section</td><td>{{ $s->schoolClass?->name ?? '—' }} / {{ $s->section?->name ?? '—' }}</td></tr>
            <tr><td class="lbl">Category</td><td>{{ $s->category?->name ?? '—' }}</td><td class="lbl">Admission Date</td><td>{{ $s->admission_date?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td class="lbl">Date of Birth</td><td>{{ $s->date_of_birth?->format('d M Y') ?? '—' }}</td><td class="lbl">Status</td><td>{{ Str::title(is_object($s->status) ? $s->status->value : ($s->status ?? '—')) }}</td></tr>
            <tr><td class="lbl">Phone</td><td>{{ $s->phone ?? '—' }}</td><td class="lbl">Previous School</td><td>{{ $s->previous_school ?? '—' }}</td></tr>
        </table>

        @if ($data['guardians']->isNotEmpty())
            <h3>Guardians</h3>
            <table>
                <thead><tr><th>Name</th><th>Relationship</th><th>Phone</th><th>Email</th></tr></thead>
                <tbody>
                    @foreach ($data['guardians'] as $g)
                        <tr><td>{{ $g->name }}</td><td>{{ $g->relationship ?? '—' }}</td><td>{{ $g->phone ?? '—' }}</td><td>{{ $g->email ?? '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($data['promotions']->isNotEmpty())
            <h3>Promotion History</h3>
            <table>
                <thead><tr><th>Date</th><th>From</th><th>To</th><th>Year</th></tr></thead>
                <tbody>
                    @foreach ($data['promotions'] as $p)
                        <tr>
                            <td>{{ $p->promoted_at?->format('d M Y') ?? $p->created_at?->format('d M Y') }}</td>
                            <td>{{ $p->fromClass?->name ?? '—' }}</td>
                            <td>{{ $p->toClass?->name ?? '—' }}</td>
                            <td>{{ $p->toAcademicYear?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    {{-- Transport --}}
    @elseif ($reportType === 'transport' && ! empty($data['rows']))
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th>Route</th><th>Stop</th><th>Direction</th><th class="c">Active</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll'] ?? '—' }}</td><td>{{ $row['student'] }}</td>
                        <td>{{ $row['route'] ?? '—' }}</td><td>{{ $row['stop'] ?? '—' }}</td>
                        <td>{{ Str::title($row['direction'] ?? '—') }}</td><td class="c">{{ $row['active'] ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Dormitory --}}
    @elseif ($reportType === 'dormitory' && ! empty($data['rows']))
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th>Building</th><th>Room</th><th class="c">Bed</th><th>Status</th><th>Check-in</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll'] ?? '—' }}</td><td>{{ $row['student'] }}</td>
                        <td>{{ $row['building'] ?? '—' }}</td><td>{{ $row['room'] ?? '—' }}</td>
                        <td class="c">{{ $row['bed'] ?? '—' }}</td><td>{{ Str::title($row['status'] ?? '—') }}</td><td>{{ $row['check_in'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Guardian --}}
    @elseif ($reportType === 'guardian' && ! empty($data['rows']))
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th>Guardian</th><th>Relationship</th><th>Phone</th><th>Email</th><th class="c">Primary</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll'] ?? '—' }}</td><td>{{ $row['student'] }}</td>
                        <td>{{ $row['guardian'] ?? '—' }}</td><td>{{ $row['relationship'] ?? '—' }}</td>
                        <td>{{ $row['phone'] ?? '—' }}</td><td>{{ $row['email'] ?? '—' }}</td><td class="c">{{ $row['is_primary'] ? 'Yes' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align:center; color:#999; margin-top:40px;">No data found for the selected criteria.</p>
    @endif

    <div class="footer">KynexEdu — {{ $title }}</div>
</body>
</html>
