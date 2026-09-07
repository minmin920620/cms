<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crime Report Charts - Crime Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .page { padding: 28px; }
        .toolbar { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; margin-bottom: 18px; }
        .btn { border-radius: 8px; color: #ffffff; display: inline-flex; font-size: 14px; font-weight: 700; padding: 10px 16px; text-decoration: none; }
        .btn-red { background: #dc2626; }
        .btn-blue { background: #2563eb; }
        .btn-gray { background: #475569; }
        .header { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 18px; padding: 22px; }
        .header h1 { color: #991b1b; font-size: 26px; margin: 0 0 6px; }
        .header p { color: #64748b; font-size: 14px; margin: 3px 0; }
        .stats { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 18px; }
        .stat { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        .stat .value { color: #dc2626; display: block; font-size: 30px; font-weight: 800; line-height: 1; }
        .stat .label { color: #64748b; display: block; font-size: 12px; font-weight: 700; margin-top: 8px; text-transform: uppercase; }
        .charts { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .panel { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; }
        .panel h2 { font-size: 17px; margin: 0; }
        .panel p { color: #64748b; font-size: 13px; margin: 4px 0 16px; }
        .chart-box { height: 330px; position: relative; }
        .table-wrap { margin-top: 18px; overflow-x: auto; }
        table { background: #ffffff; border: 1px solid #e2e8f0; border-collapse: collapse; border-radius: 8px; overflow: hidden; width: 100%; }
        th { background: #991b1b; color: #ffffff; font-size: 12px; padding: 10px 12px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; color: #334155; font-size: 13px; padding: 9px 12px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .empty { background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; padding: 40px; text-align: center; }
        .footer { color: #94a3b8; font-size: 12px; margin-top: 24px; text-align: center; }
        @media (max-width: 900px) {
            .page { padding: 16px; }
            .stats, .charts { grid-template-columns: 1fr; }
        }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { background: #ffffff; }
            .page { padding: 0; }
            .toolbar { display: none; }
            .header { margin-bottom: 10px; padding: 14px 16px; }
            .header h1 { font-size: 22px; }
            .header p { font-size: 11px; }
            .stats { gap: 8px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 10px; }
            .stat { padding: 10px 12px; }
            .stat .value { font-size: 22px; }
            .stat .label { font-size: 9px; }
            .charts { gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .panel { border-color: #dbe3ec; padding: 12px; page-break-inside: avoid; }
            .panel h2 { font-size: 14px; }
            .panel p { font-size: 10px; margin-bottom: 8px; }
            .chart-box { height: 235px; }
            .table-wrap { page-break-before: always; }
            th { font-size: 9px; padding: 7px 8px; }
            td { font-size: 10px; padding: 6px 8px; }
            .footer { font-size: 10px; margin-top: 10px; }
            .panel, .header, .stat { break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('F d, Y h:i A');
        $statusLabels = $summary['by_status']->mapWithKeys(fn ($count, $status) => [
            \App\Models\Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) => $count,
        ]);
        $topStatus = $statusLabels->sortDesc()->keys()->first() ?? 'No Status';
        $topBarangay = $summary['by_barangay']->keys()->first() ?? 'No Barangay';
        $topCrime = $summary['by_type']->keys()->first() ?? 'No Crime Type';
        $chartColors = ['#dc2626', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#ea580c', '#475569', '#0d9488', '#9333ea'];
    @endphp

    <main class="page">
        <div class="toolbar">
            <a class="btn btn-red" href="#" onclick="window.print(); return false;">Print / Save</a>
            <a class="btn btn-blue" href="{{ route('reports.export-pdf', request()->query()) }}">Download PDF</a>
            <a class="btn btn-gray" href="{{ route('reports.index') }}">Back</a>
        </div>

        <section class="header">
            <h1>Crime Report Charts</h1>
            <p>Generated on {{ $displayTime(now()) }}</p>
            @if($dateFrom || $dateTo)
                <p>Period: {{ $dateFrom ? date('F d, Y', strtotime($dateFrom)) : 'Earliest' }} - {{ $dateTo ? date('F d, Y', strtotime($dateTo)) : 'Latest' }}</p>
            @endif
        </section>

        <section class="stats">
            <div class="stat"><span class="value">{{ $summary['total'] }}</span><span class="label">Incidents Found</span></div>
            <div class="stat"><span class="value">{{ $summary['by_barangay']->count() }}</span><span class="label">Barangays Covered</span></div>
            <div class="stat"><span class="value">{{ $summary['by_type']->count() }}</span><span class="label">Crime Types</span></div>
            <div class="stat"><span class="value">{{ $summary['monthly']->count() }}</span><span class="label">Months Covered</span></div>
        </section>

        @if($crimes->count() > 0)
            <section class="charts">
                <div class="panel">
                    <h2>Crimes by Barangay</h2>
                    <p>Highest count: {{ $topBarangay }}</p>
                    <div class="chart-box"><canvas id="barangayPie"></canvas></div>
                </div>
                <div class="panel">
                    <h2>Crime Type Percentage</h2>
                    <p>Largest share: {{ $topCrime }}</p>
                    <div class="chart-box"><canvas id="crimeTypePie"></canvas></div>
                </div>
                <div class="panel">
                    <h2>Incidents per Month</h2>
                    <p>Bar graph of reported volume by month.</p>
                    <div class="chart-box"><canvas id="monthlyBar"></canvas></div>
                </div>
                <div class="panel">
                    <h2>Monthly Trend</h2>
                    <p>Line graph showing whether cases are rising or falling.</p>
                    <div class="chart-box"><canvas id="monthlyLine"></canvas></div>
                </div>
            </section>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Title</th>
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
                                <td style="font-family: monospace;">{{ $crime->case_number }}</td>
                                <td>{{ $crime->title }}</td>
                                <td>{{ $crime->crimeType->name ?? 'N/A' }}</td>
                                <td>{{ $crime->barangay->name ?? 'N/A' }}</td>
                                <td>{{ $crime->date_occurred->format('M d, Y') }}</td>
                                <td>{{ $crime->status_label }}</td>
                                <td>{{ $crime->assignedOfficer->name ?? 'Unassigned' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty">No incidents match the selected filters.</p>
        @endif

        <div class="footer">Crime Management System &copy; {{ date('Y') }}. This is a system-generated report.</div>
    </main>

    @if($crimes->count() > 0)
        <script>
            const colors = @json($chartColors);
            const barangayLabels = @json($summary['by_barangay']->keys()->values());
            const barangayValues = @json($summary['by_barangay']->values());
            const crimeTypeLabels = @json($summary['by_type']->keys()->values());
            const crimeTypeValues = @json($summary['by_type']->values());
            const monthlyLabels = @json($summary['monthly']->pluck('label'));
            const monthlyValues = @json($summary['monthly']->pluck('count'));

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } }
                }
            };

            const charts = [];

            charts.push(new Chart(document.getElementById('barangayPie'), {
                type: 'pie',
                data: { labels: barangayLabels, datasets: [{ data: barangayValues, backgroundColor: colors }] },
                options: baseOptions
            }));

            charts.push(new Chart(document.getElementById('crimeTypePie'), {
                type: 'doughnut',
                data: { labels: crimeTypeLabels, datasets: [{ data: crimeTypeValues, backgroundColor: colors }] },
                options: {
                    ...baseOptions,
                    plugins: {
                        ...baseOptions.plugins,
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const total = crimeTypeValues.reduce((sum, value) => sum + Number(value), 0);
                                    const percent = total ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.parsed} (${percent}%)`;
                                }
                            }
                        }
                    }
                }
            }));

            charts.push(new Chart(document.getElementById('monthlyBar'), {
                type: 'bar',
                data: { labels: monthlyLabels, datasets: [{ label: 'Incidents', data: monthlyValues, backgroundColor: '#dc2626', borderRadius: 6 }] },
                options: { ...baseOptions, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            }));

            charts.push(new Chart(document.getElementById('monthlyLine'), {
                type: 'line',
                data: { labels: monthlyLabels, datasets: [{ label: 'Incidents', data: monthlyValues, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.12)', fill: true, tension: 0.35, pointRadius: 4 }] },
                options: { ...baseOptions, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            }));

            window.addEventListener('beforeprint', () => {
                charts.forEach((chart) => chart.resize());
            });

            window.addEventListener('afterprint', () => {
                charts.forEach((chart) => chart.resize());
            });
        </script>
    @endif
</body>
</html>
