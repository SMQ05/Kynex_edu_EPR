<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #666; font-size: 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1a1a2e; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
        tr:nth-child(even) { background: #f8f8f8; }
        .footer { margin-top: 16px; text-align: center; color: #999; font-size: 8px; }
    </style>
</head>
<body>
    <h1>{{ $report->name }}</h1>
    <div class="meta">
        Generated: {{ now()->format('d M Y, h:i A') }}
        | Source: {{ $report->base_model }}
        | Total Rows: {{ count($results) }}
    </div>

    @if(count($results) > 0)
        <table>
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($results as $row)
                    <tr>
                        @foreach($row as $value)
                            <td>{{ $value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align:center; color:#999; margin-top:40px;">No data found for the selected criteria.</p>
    @endif

    <div class="footer">
        KynexEdu — Custom Report Builder | {{ $report->name }}
    </div>
</body>
</html>
