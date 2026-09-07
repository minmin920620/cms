<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Analytics Summary Report</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #eeeae6; color: #0f172a; font-family: Arial, sans-serif; font-size: 10px; line-height: 1.35; margin: 0; padding: 26px 0; }
        .sheet { background: #fff; box-shadow: 0 1px 4px rgba(15, 23, 42, .18); margin: 0 auto 24px; min-height: 760px; padding: 28px 28px 24px; width: 1100px; }
        .table-sheet { padding: 36px 34px 24px; }
        .header { border-bottom: 2px solid #dc2626; margin-bottom: 10px; padding-bottom: 8px; text-align: center; }
        .header h1 { color: #991b1b; font-size: 20px; margin: 0 0 3px; }
        .header p { color: #64748b; margin: 1px 0; }
        .summary { border-collapse: collapse; margin-bottom: 12px; width: 100%; }
        .summary td { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; text-align: center; width: 25%; }
        .summary .value { color: #dc2626; display: block; font-size: 18px; font-weight: 700; }
        .summary .label { color: #475569; display: block; font-size: 8px; text-transform: uppercase; }
        .grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .panel { border: 1px solid #dbe3ef; border-radius: 6px; min-height: 250px; padding: 10px; }
        .panel h2 { font-size: 12px; margin: 0 0 2px; }
        .panel p { color: #64748b; font-size: 9px; margin: 0 0 8px; }
        .chart { display: block; height: auto; margin: 4px auto 8px; width: 245px; }
        .wide-chart { width: 330px; }
        .legend { text-align: center; }
        .legend-item { color: #475569; display: inline-block; font-size: 7px; margin: 2px 5px; white-space: nowrap; }
        .swatch { display: inline-block; height: 7px; margin-right: 3px; vertical-align: -1px; width: 7px; }
        .records { border-collapse: collapse; margin-top: 0; table-layout: fixed; width: 100%; }
        .records th { background: #991b1b; box-shadow: inset 0 0 0 9999px #991b1b; color: #fff; font-size: 8px; padding: 6px 8px; text-align: left; text-transform: uppercase; }
        .records td { border-bottom: 1px solid #e5e7eb; color: #334155; font-size: 8px; padding: 5px 8px; }
        .records th:nth-child(1), .records td:nth-child(1) { width: 21%; }
        .records th:nth-child(2), .records td:nth-child(2) { width: 15%; }
        .records th:nth-child(3), .records td:nth-child(3) { width: 21%; }
        .records th:nth-child(4), .records td:nth-child(4) { width: 14%; }
        .records th:nth-child(5), .records td:nth-child(5) { width: 18%; }
        .records th:nth-child(6), .records td:nth-child(6) { width: 19%; }
        .footer { color: #94a3b8; font-size: 9px; margin-top: 18px; text-align: center; }
        .print-button { margin: 0 auto 14px; text-align: right; width: 1100px; }
        .print-button button { background: #111827; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: 9px 14px; }
        .empty { color: #64748b; padding: 60px 0; text-align: center; }
        @media print {
            @page { margin: 12px; size: A4 landscape; }
            body { background: #fff; padding: 0; }
            .print-button { display: none; }
            .sheet { box-shadow: none; margin: 0; min-height: 0; page-break-after: always; width: 100%; }
            .table-sheet { padding: 36px 32px 18px; }
            .sheet:last-child { page-break-after: auto; }
            .records th { font-size: 7.5px; padding: 5px 7px; }
            .records td { font-size: 7.5px; padding: 4px 7px; }
            .footer { font-size: 8px; margin-top: 15px; }
        }
    </style>
</head>
<body>
    @php
        $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('F d, Y h:i A');
        $colors = ['#dc2626', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#ea580c', '#475569', '#0d9488', '#9333ea'];
        $totalIncidents = max((int) $summary['total'], 1);
        $byBarangay = collect($summary['by_barangay']);
        $byType = collect($summary['by_type']);
        $monthly = collect($summary['monthly'])->values();
        $topBarangay = $byBarangay->first()['label'] ?? 'No Barangay';
        $topCrime = $byType->first()['label'] ?? 'No Crime Type';
        $maxMonthly = max($monthly->pluck('count')->all() ?: [1]);

    @endphp

    <div class="print-button">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <section class="sheet table-sheet">
        <div class="header">
            <h1>LGU Crime Report Charts</h1>
            <p>Crime Analytics Summary Report</p>
            <p>Generated on {{ $displayTime($generatedAt) }}</p>
            @if($dateFrom || $dateTo)
                <p>Period: {{ $dateFrom ? date('F d, Y', strtotime($dateFrom)) : 'Earliest' }} - {{ $dateTo ? date('F d, Y', strtotime($dateTo)) : 'Latest' }}</p>
            @endif
        </div>

        <table class="summary">
            <tr>
                <td><span class="value">{{ $summary['total'] }}</span><span class="label">Total Incidents Found</span></td>
                <td><span class="value">{{ $byBarangay->count() }}</span><span class="label">Barangays Covered</span></td>
                <td><span class="value">{{ $byType->count() }}</span><span class="label">Crime Types</span></td>
                <td><span class="value">{{ $monthly->count() }}</span><span class="label">Months Covered</span></td>
            </tr>
        </table>

        @if($summary['total'] > 0)
            <div class="grid">
                <div class="panel">
                    <h2>Incidents by Barangay</h2>
                    <p>Highest count: {{ $topBarangay }}</p>
                    <img class="chart" width="245" src="{{ $chartImages['barangay'] }}" alt="Incidents by Barangay">
                    <div class="legend">
                        @foreach($byBarangay->take(12) as $item)
                            <span class="legend-item"><span class="swatch" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>{{ $item['label'] }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="panel">
                    <h2>Crime Type Percentage</h2>
                    <p>Largest share: {{ $topCrime }}</p>
                    <img class="chart" width="245" src="{{ $chartImages['type'] }}" alt="Crime Type Percentage">
                    <div class="legend">
                        @foreach($byType->take(12) as $item)
                            @php($percent = round(((int) $item['count'] / $totalIncidents) * 100, 1))
                            <span class="legend-item"><span class="swatch" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>{{ $item['label'] }} {{ $percent }}%</span>
                        @endforeach
                    </div>
                </div>

                <div class="panel">
                    <h2>Incidents per Month</h2>
                    <p>Bar graph of reported volume by month.</p>
                    <img class="chart wide-chart" width="330" src="{{ $chartImages['monthlyBar'] }}" alt="Incidents per Month">
                </div>

                <div class="panel">
                    <h2>Monthly Trend</h2>
                    <p>Line graph showing whether cases are rising or falling.</p>
                    <img class="chart wide-chart" width="330" src="{{ $chartImages['monthlyLine'] }}" alt="Monthly Trend">
                </div>
            </div>
        @else
            <p class="empty">No incidents match the selected filters.</p>
        @endif
    </section>

    <section class="sheet">
        <table class="records">
            <thead>
                <tr>
                    <th>Case #</th>
                    <th>Type</th>
                    <th>Barangay</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Officer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($crimes as $crime)
                    <tr>
                        <td>{{ $crime->case_number }}</td>
                        <td>{{ $crime->crimeType->name ?? 'N/A' }}</td>
                        <td>{{ $crime->barangay->name ?? 'N/A' }}</td>
                        <td>{{ $crime->date_occurred->format('M d, Y') }}</td>
                        <td>{{ $crime->status_label }}</td>
                        <td>{{ $crime->assignedOfficer->name ?? 'Unassigned' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Crime Management System &copy; {{ date('Y') }}. This is a system-generated report.
        </div>
    </section>
</body>
</html>
