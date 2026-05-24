<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admit Card — {{ $exam->name }}</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
        .card {
            border: 2px solid #065f46;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .card-header { text-align: center; margin-bottom: 10px; }
        .school-name { font-size: 14pt; font-weight: bold; color: #065f46; }
        .doc-title { font-size: 12pt; font-weight: bold; margin-top: 2px; letter-spacing: 1px; }
        .exam-name { font-size: 10pt; color: #4b5563; }
        .top { width: 100%; }
        .top td { vertical-align: top; }
        .photo-box {
            width: 90px; height: 110px;
            border: 1px solid #9ca3af;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
        }
        .photo-box img { width: 90px; height: 110px; object-fit: cover; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 6px; }
        .meta td.label { font-weight: bold; width: 110px; color: #4b5563; }
        table.sched { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.sched th, table.sched td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; font-size: 9pt; }
        table.sched th { background: #ecfdf5; color: #065f46; }
        .footer { margin-top: 16px; font-size: 8pt; color: #6b7280; }
        .sign { margin-top: 26px; text-align: right; }
        .sign span { border-top: 1px solid #9ca3af; padding-top: 4px; font-size: 9pt; color: #6b7280; }
    </style>
</head>
<body>
    @foreach($students as $student)
        @php $schedules = $schedulesByClass[$student->class_id] ?? collect(); @endphp
        <div class="card">
            <div class="card-header">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="doc-title">ADMIT CARD</div>
                <div class="exam-name">{{ $exam->name }}{{ $exam->academicYear ? ' — ' . $exam->academicYear->name : '' }}</div>
            </div>

            <table class="top">
                <tr>
                    <td>
                        <table class="meta">
                            <tr>
                                <td class="label">Name</td>
                                <td>{{ $student->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Admission #</td>
                                <td>{{ $student->admission_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Roll #</td>
                                <td>{{ $student->roll_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Class</td>
                                <td>{{ $student->schoolClass?->name ?? '—' }}{{ $student->section ? ' (' . $student->section->name . ')' : '' }}</td>
                            </tr>
                            @if($settings['show_dob'] ?? false)
                                <tr>
                                    <td class="label">Date of Birth</td>
                                    <td>{{ optional($student->date_of_birth)->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                    <td style="width: 100px; text-align: right;">
                        <div class="photo-box">
                            @if($student->photo_data_uri)
                                <img src="{{ $student->photo_data_uri }}" alt="photo">
                            @else
                                Photo
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            @if($schedules->isNotEmpty())
                <table class="sched">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Full Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $sch)
                            <tr>
                                <td>{{ $sch->subject?->name ?? '—' }}</td>
                                <td>{{ optional($sch->exam_date)->format('d M Y') }}</td>
                                <td>{{ $sch->start_time ? \Illuminate\Support\Carbon::parse($sch->start_time)->format('h:i A') : '—' }}{{ $sch->end_time ? ' - ' . \Illuminate\Support\Carbon::parse($sch->end_time)->format('h:i A') : '' }}</td>
                                <td>{{ $sch->room ?? '—' }}</td>
                                <td>{{ $sch->full_marks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(! empty($settings['instructions'] ?? null))
                <div class="footer">{{ $settings['instructions'] }}</div>
            @endif

            <div class="sign"><span>Controller of Examinations</span></div>
        </div>
    @endforeach
</body>
</html>
