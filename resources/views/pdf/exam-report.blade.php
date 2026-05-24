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
        tfoot td { background: #eef; font-weight: bold; }
        .footer { margin-top: 16px; text-align: center; color: #999; font-size: 8px; }
        .summary-box { border: 1px solid #ccc; padding: 8px; margin-top: 10px; }
        .summary-box span { display: inline-block; margin-right: 16px; }
    </style>
</head>
<body>
    <div class="school">{{ $tenant?->name ?? 'School Report' }}</div>
    <h1>{{ $title }}</h1>
    @if ($heading)<div class="meta">{{ $heading }}</div>@endif
    <div class="meta">Generated: {{ now()->format('d M Y, h:i A') }}</div>

    {{-- Tabulation Sheet --}}
    @if ($reportType === 'tabulation' && ! empty($data['rows']))
        <table>
            <thead>
                <tr>
                    <th>Roll</th><th>Student</th>
                    @foreach ($data['subjects'] as $subject)<th class="c">{{ $subject['name'] }} (/{{ $subject['full_marks'] }})</th>@endforeach
                    <th class="c">Total</th><th class="c">%</th><th class="c">Grade</th><th class="c">Rank</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll_number'] ?? '—' }}</td>
                        <td>{{ $row['name'] }}</td>
                        @foreach ($row['cells'] as $cell)<td class="c">{{ $cell['is_absent'] ? 'Abs' : ($cell['value'] ?? '—') }}</td>@endforeach
                        <td class="c b">{{ $row['obtained'] }}/{{ $row['total'] }}</td>
                        <td class="c">{{ $row['percentage'] }}%</td>
                        <td class="c">{{ $row['grade'] ?? '—' }}</td>
                        <td class="c">{{ $row['rank'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Mark Sheet / Progress Card --}}
    @elseif (in_array($reportType, ['marksheet', 'progress_card']) && ! empty($data['card']))
        @php $card = $data['card']; @endphp
        <p class="b">{{ $card['student']->full_name ?? '—' }}
            @if (! empty($card['student']->admission_number)) ({{ $card['student']->admission_number }}) @endif</p>
        <table>
            <thead><tr><th>Subject</th><th class="c">Full</th><th class="c">Pass</th><th class="c">Obtained</th><th class="c">Result</th></tr></thead>
            <tbody>
                @foreach ($card['subjects'] as $subject)
                    <tr>
                        <td>{{ $subject['subject'] }}</td>
                        <td class="c">{{ $subject['full_marks'] }}</td>
                        <td class="c">{{ $subject['pass_marks'] }}</td>
                        <td class="c">{{ $subject['is_absent'] ? 'Absent' : ($subject['marks_obtained'] ?? '—') }}</td>
                        <td class="c">{{ $subject['is_pass'] ? 'Pass' : 'Fail' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Total</td><td class="c">{{ $card['total_marks'] }}</td><td></td><td class="c">{{ $card['obtained'] }}</td><td class="c">{{ $card['percentage'] }}%</td></tr></tfoot>
        </table>
        <div class="summary-box">
            <span>Grade: <b>{{ $card['grade'] ?? '—' }}</b></span>
            <span>Grade Point: <b>{{ $card['grade_point'] ?? '—' }}</b></span>
            <span>Rank: <b>{{ $card['rank'] ?? '—' }}</b></span>
            <span>Status: <b>{{ $card['status']?->label() ?? '—' }}</b></span>
            @if ($reportType === 'progress_card' && ! empty($data['classSummary']))
                <br><span>Class Average: <b>{{ $data['classSummary']['average'] }}%</b></span>
                <span>Highest: <b>{{ $data['classSummary']['highest'] }}%</b></span>
                <span>Pass Rate: <b>{{ $data['classSummary']['pass_rate'] }}%</b></span>
            @endif
        </div>

    {{-- Subject-wise Marksheet --}}
    @elseif ($reportType === 'subject_wise' && ! empty($data['rows']))
        <div class="summary-box">
            <span>Subject: <b>{{ $data['subject'] }}</b> (/{{ $data['full_marks'] }})</span>
            <span>Appeared: <b>{{ $data['stats']['appeared'] }}</b></span>
            <span>Passed: <b>{{ $data['stats']['passed'] }}</b></span>
            <span>Highest: <b>{{ $data['stats']['highest'] }}</b></span>
            <span>Lowest: <b>{{ $data['stats']['lowest'] }}</b></span>
            <span>Average: <b>{{ $data['stats']['average'] }}</b></span>
        </div>
        <table>
            <thead><tr><th>Roll</th><th>Student</th><th class="c">Marks</th><th class="c">Result</th><th>Remarks</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['roll_number'] ?? '—' }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="c">{{ $row['is_absent'] ? 'Absent' : ($row['marks'] ?? '—') }}</td>
                        <td class="c">{{ $row['is_pass'] === null ? '—' : ($row['is_pass'] ? 'Pass' : 'Fail') }}</td>
                        <td>{{ $row['remarks'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Merit List --}}
    @elseif ($reportType === 'merit_list' && ! empty($data['rows']))
        <table>
            <thead><tr><th class="c">Position</th><th>Student</th><th>Admission</th><th class="c">Marks</th><th class="c">%</th><th class="c">Grade</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td class="c b">{{ $row['position'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['admission'] ?? '—' }}</td>
                        <td class="c">{{ $row['obtained'] }}/{{ $row['total'] }}</td>
                        <td class="c">{{ $row['percentage'] }}%</td>
                        <td class="c">{{ $row['grade'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Previous Result --}}
    @elseif ($reportType === 'previous_result' && ! empty($data['rows']))
        <table>
            <thead><tr><th class="c">Rank</th><th>Student</th><th>Admission</th><th>Year</th><th class="c">Final %</th><th class="c">Grade</th><th class="c">Status</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td class="c">{{ $row['rank'] ?? '—' }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['admission'] ?? '—' }}</td>
                        <td>{{ $row['year'] ?? '—' }}</td>
                        <td class="c">{{ $row['percentage'] ?? '—' }}%</td>
                        <td class="c">{{ $row['grade'] ?? '—' }}</td>
                        <td class="c">{{ Str::title($row['status'] ?? '—') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- Exam Routine --}}
    @elseif ($reportType === 'exam_routine' && ! empty($data['rows']))
        <table>
            <thead><tr><th>Date</th><th class="c">Time</th><th>Class</th><th>Section</th><th>Subject</th><th>Room</th><th class="c">Full Marks</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['date'] ?? '—' }}</td>
                        <td class="c">{{ $row['start'] ?? '—' }}@if($row['end']) – {{ $row['end'] }}@endif</td>
                        <td>{{ $row['class'] ?? '—' }}</td>
                        <td>{{ $row['section'] ?? '—' }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td>{{ $row['room'] ?? '—' }}</td>
                        <td class="c">{{ $row['full_marks'] }}</td>
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
