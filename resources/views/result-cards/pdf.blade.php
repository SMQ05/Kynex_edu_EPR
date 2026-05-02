<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Result Card — {{ $result->student?->first_name }} {{ $result->student?->last_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1f2937; }
        h1 { margin: 0 0 4px 0; font-size: 18pt; }
        h2 { margin: 16px 0 6px 0; font-size: 13pt; color: #065f46; border-bottom: 1px solid #d1d5db; padding-bottom: 2px; }
        .header { text-align: center; margin-bottom: 18px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #065f46; }
        .meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-grid td { padding: 4px 8px; }
        .meta-grid td.label { font-weight: bold; width: 30%; color: #4b5563; }
        table.scores { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.scores th, table.scores td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        table.scores th { background: #f3f4f6; }
        .pct { text-align: right; }
        .summary { margin-top: 16px; padding: 10px; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 4px; }
        .summary-grid { width: 100%; }
        .summary-grid td { padding: 4px 0; }
        .pass { color: #047857; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 9pt; color: #6b7280; text-align: center; }
        .signatures { margin-top: 40px; display: table; width: 100%; }
        .signatures div { display: table-cell; text-align: center; padding-top: 30px; border-top: 1px solid #9ca3af; width: 50%; font-size: 10pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $tenant?->school_name ?? config('app.name') }}</div>
        <h1>Annual Result Card</h1>
        <div>Academic Year: {{ $result->academicYear?->name }}</div>
    </div>

    <table class="meta-grid">
        <tr>
            <td class="label">Student Name</td>
            <td>{{ $result->student?->first_name }} {{ $result->student?->last_name }}</td>
            <td class="label">Admission #</td>
            <td>{{ $result->student?->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td>{{ $result->schoolClass?->name }}</td>
            <td class="label">Section</td>
            <td>{{ $result->section?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Roll #</td>
            <td>{{ $result->student?->roll_number ?? '—' }}</td>
            <td class="label">Generated</td>
            <td>{{ optional($result->generated_at)->format('d M Y, h:i A') ?? '—' }}</td>
        </tr>
    </table>

    <h2>Component Breakdown</h2>
    <table class="scores">
        <thead>
            <tr>
                <th>Component</th>
                <th class="pct">Score</th>
                <th class="pct">Weight</th>
                <th class="pct">Contribution</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Exams (all terms, weighted)</td>
                <td class="pct">{{ number_format((float) $result->exam_score, 2) }}%</td>
                <td class="pct">{{ $result->exam_weight_percent }}%</td>
                <td class="pct">{{ number_format(((float) $result->exam_score * $result->exam_weight_percent) / 100, 2) }}%</td>
            </tr>
            <tr>
                <td>Homework</td>
                <td class="pct">{{ number_format((float) $result->homework_score, 2) }}%</td>
                <td class="pct">{{ $result->homework_weight_percent }}%</td>
                <td class="pct">{{ number_format(((float) $result->homework_score * $result->homework_weight_percent) / 100, 2) }}%</td>
            </tr>
            <tr>
                <td>Class Assignments</td>
                <td class="pct">{{ number_format((float) $result->class_assignment_score, 2) }}%</td>
                <td class="pct">{{ $result->class_assignment_weight_percent }}%</td>
                <td class="pct">{{ number_format(((float) $result->class_assignment_score * $result->class_assignment_weight_percent) / 100, 2) }}%</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <table class="summary-grid">
            <tr>
                <td><strong>Final Percentage:</strong></td>
                <td>{{ number_format((float) $result->final_percentage, 2) }}%</td>
                <td><strong>Grade:</strong></td>
                <td>{{ $result->grade ?? '—' }} @if ($result->grade_point) ({{ number_format((float) $result->grade_point, 2) }} GPA) @endif</td>
            </tr>
            <tr>
                <td><strong>Rank in Class:</strong></td>
                <td>{{ $result->rank ? '#' . $result->rank : '—' }}</td>
                <td><strong>Result:</strong></td>
                <td class="{{ $result->status === 'pass' ? 'pass' : 'fail' }}">{{ strtoupper($result->status) }}</td>
            </tr>
        </table>
        @if ($result->remarks)
            <p style="margin-top:8px"><strong>Remarks:</strong> {{ $result->remarks }}</p>
        @endif
    </div>

    <div class="signatures">
        <div>Class Teacher</div>
        <div>Principal / Institute Head</div>
    </div>

    <div class="footer">
        Generated by KynexEdu — {{ now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
