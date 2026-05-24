<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Seat Plan — {{ $exam->name }}</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; }
        .school-name { font-size: 14pt; font-weight: bold; color: #065f46; }
        .doc-title { font-size: 12pt; font-weight: bold; margin-top: 2px; letter-spacing: 1px; }
        h2 { font-size: 11pt; color: #065f46; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; page-break-after: avoid; }
        table.seats { width: 100%; border-collapse: collapse; margin-bottom: 8px; page-break-inside: auto; }
        table.seats th, table.seats td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; font-size: 9pt; }
        table.seats th { background: #ecfdf5; color: #065f46; }
        .muted { color: #6b7280; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $schoolName }}</div>
        <div class="doc-title">SEAT PLAN</div>
        <div class="muted">{{ $exam->name }}{{ $exam->academicYear ? ' — ' . $exam->academicYear->name : '' }}</div>
    </div>

    @forelse($byRoom as $room => $seats)
        <h2>Room: {{ $room }} <span class="muted">({{ $seats->count() }} students)</span></h2>
        <table class="seats">
            <thead>
                <tr>
                    <th style="width: 70px;">Seat</th>
                    <th style="width: 70px;">Roll</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Admission #</th>
                </tr>
            </thead>
            <tbody>
                @foreach($seats as $seat)
                    <tr>
                        <td>{{ $seat->seat_number ?? '—' }}</td>
                        <td>{{ $seat->student?->roll_number ?? '—' }}</td>
                        <td>{{ $seat->student?->full_name ?? '—' }}</td>
                        <td>{{ $seat->schoolClass?->name ?? $seat->student?->schoolClass?->name ?? '—' }}{{ $seat->section ? ' (' . $seat->section->name . ')' : '' }}</td>
                        <td>{{ $seat->student?->admission_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="muted">No seat allocations found for this exam.</p>
    @endforelse
</body>
</html>
