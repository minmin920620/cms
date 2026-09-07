<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Crime Incident Report</title>
    <style>
        * { box-sizing: border-box; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.35; margin: 14px; }
        @page { margin: 12px; size: A4 landscape; }
        .header { border-bottom: 2px solid #dc2626; margin-bottom: 10px; padding-bottom: 8px; text-align: center; }
        .header h1 { color: #991b1b; font-size: 20px; margin: 0 0 3px; }
        .header p { color: #64748b; margin: 1px 0; }
        .summary { border-collapse: collapse; margin-bottom: 10px; width: 100%; }
        .summary td { background: #f8fafc; border: 1px solid #e2e8f0; padding: 7px; text-align: center; width: 25%; }
        .summary .value { color: #dc2626; display: block; font-size: 17px; font-weight: bold; }
        .summary .label { color: #64748b; display: block; font-size: 8px; text-transform: uppercase; }
        .grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .grid td { padding: 5px; vertical-align: top; width: 50%; }
        .panel { border: 1px solid #e2e8f0; border-radius: 6px; height: 245px; overflow: hidden; padding: 8px; }
        .panel h2 { color: #0f172a; font-size: 12px; margin: 0 0 2px; }
        .panel p { color: #64748b; font-size: 9px; margin: 0 0 5px; }
        .chart { display: block; height: auto; margin: 0 auto 4px; width: 245px; }
        .wide-chart { height: auto; width: 330px; }
        .legend { text-align: center; }
        .legend-item { color: #475569; display: inline-block; font-size: 7px; margin: 1px 4px; white-space: nowrap; }
        .swatch { display: inline-block; height: 7px; margin-right: 3px; vertical-align: -1px; width: 7px; }
        .footer { border-top: 1px solid #e5e7eb; color: #94a3b8; font-size: 9px; margin-top: 10px; padding-top: 7px; text-align: center; }
        .records { border-collapse: collapse; margin-top: 8px; page-break-before: always; width: 100%; }
        .records th { background: #991b1b; color: #ffffff; font-size: 8px; padding: 5px; text-align: left; text-transform: uppercase; }
        .records td { border-bottom: 1px solid #e5e7eb; color: #334155; font-size: 8px; padding: 4px 5px; }
        .empty { color: #64748b; padding: 28px 0; text-align: center; }
    </style>
</head>
<body>
    @php
        $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('F d, Y h:i A');
        $colors = ['#dc2626', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#ea580c', '#475569', '#0d9488', '#9333ea'];
        $totalIncidents = max((int) $summary['total'], 1);
        $topBarangay = $summary['by_barangay']->keys()->first() ?? 'No Barangay';
        $topCrime = $summary['by_type']->keys()->first() ?? 'No Crime Type';
    @endphp

    <div class="header">
        <h1>Crime Report Charts</h1>
        <p>Generated on {{ $displayTime(now()) }}</p>
        @if($dateFrom || $dateTo)
            <p>Period: {{ $dateFrom ? date('F d, Y', strtotime($dateFrom)) : 'Earliest' }} - {{ $dateTo ? date('F d, Y', strtotime($dateTo)) : 'Latest' }}</p>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $summary['total'] }}</span><span class="label">Incidents Found</span></td>
            <td><span class="value">{{ $summary['by_barangay']->count() }}</span><span class="label">Barangays Covered</span></td>
            <td><span class="value">{{ $summary['by_type']->count() }}</span><span class="label">Crime Types</span></td>
            <td><span class="value">{{ $summary['monthly']->count() }}</span><span class="label">Months Covered</span></td>
        </tr>
    </table>

    @if($crimes->count() > 0)
        <table class="grid">
            <tr>
                <td>
                    <div class="panel">
                        <h2>Crimes by Barangay</h2>
                        <p>Highest count: {{ $topBarangay }}</p>
                        <img class="chart" width="245" src="{{ $chartImages['barangay'] }}" alt="Crimes by Barangay">
                        <div class="legend">
                            @foreach($summary['by_barangay']->take(12) as $label => $count)
                                <span class="legend-item"><span class="swatch" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </td>
                <td>
                    <div class="panel">
                        <h2>Crime Type Percentage</h2>
                        <p>Largest share: {{ $topCrime }}</p>
                        <img class="chart" width="245" src="{{ $chartImages['type'] }}" alt="Crime Type Percentage">
                        <div class="legend">
                            @foreach($summary['by_type']->take(12) as $label => $count)
                                @php $percent = round(($count / $totalIncidents) * 100, 1); @endphp
                                <span class="legend-item"><span class="swatch" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>{{ $label }} {{ $percent }}%</span>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="panel">
                        <h2>Incidents per Month</h2>
                        <p>Bar graph of reported volume by month.</p>
                        <img class="chart wide-chart" width="330" src="{{ $chartImages['monthlyBar'] }}" alt="Incidents per Month">
                    </div>
                </td>
                <td>
                    <div class="panel">
                        <h2>Monthly Trend</h2>
                        <p>Line graph showing whether cases are rising or falling.</p>
                        <img class="chart wide-chart" width="330" src="{{ $chartImages['monthlyLine'] }}" alt="Monthly Trend">
                    </div>
                </td>
            </tr>
        </table>

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
                @foreach($crimes as $crime)
                    <tr>
                        <td>{{ $crime->case_number }}</td>
                        <td>{{ $crime->crimeType->name ?? 'N/A' }}</td>
                        <td>{{ $crime->barangay->name ?? 'N/A' }}</td>
                        <td>{{ $crime->date_occurred->format('M d, Y') }}</td>
                        <td>{{ $crime->status_label }}</td>
                        <td>{{ $crime->assignedOfficer->name ?? 'Unassigned' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No incidents match the selected filters.</p>
    @endif

    <div class="footer">
        Crime Management System &copy; {{ date('Y') }}. This is a system-generated report.
    </div>
</body>
</html>
