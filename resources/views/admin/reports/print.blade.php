<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: "Source Sans 3", "Segoe UI", sans-serif; color: #122; margin: 24px; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f2f4f3; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 12px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <h1>{{ $title }}</h1>
    <div class="meta">
        Generated {{ now()->format('d M Y H:i') }}
        @if ($filters['date_from'] || $filters['date_to'])
            · Period: {{ $filters['date_from'] ?: '…' }} → {{ $filters['date_to'] ?: '…' }}
        @endif
        @if ($filters['status'])
            · Status: {{ $filters['status'] }}
        @endif
        · Rows: {{ count($rows) }}
    </div>

    @if (count($rows) === 0)
        <p>No records for the selected filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach (array_keys($rows[0]) as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
