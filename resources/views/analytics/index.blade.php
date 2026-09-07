@extends('layouts.app')

@section('title', 'Analytics')
@section('page_title', 'Analytics & Reports')

@section('content')
<div class="space-y-6">
    @if(auth()->user()->isLgu())
        <div class="material-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Analytics Summary Report</h3>
                    <p class="mt-1 text-sm text-gray-500">Generate a printable statistical report for planning and record keeping.</p>
                </div>
                <form method="GET" action="{{ route('analytics.summary-report') }}" target="_blank" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <label for="analytics_date_from" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">From</label>
                        <input type="date" id="analytics_date_from" name="date_from" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="analytics_date_to" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">To</label>
                        <input type="date" id="analytics_date_to" name="date_to" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Generate Report
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="material-card p-4 text-center">
            <p class="text-3xl font-bold text-gray-900" id="statTotal">-</p>
            <p class="text-sm text-gray-500">Total Cases</p>
        </div>
        <div class="material-card p-4 text-center">
            <p class="text-3xl font-bold text-yellow-500" id="statPending">-</p>
            <p class="text-sm text-gray-500">Open Workflow</p>
        </div>
        <div class="material-card p-4 text-center">
            <p class="text-3xl font-bold text-blue-500" id="statInvestigating">-</p>
            <p class="text-sm text-gray-500">CIRAS / Review</p>
        </div>
        <div class="material-card p-4 text-center">
            <p class="text-3xl font-bold text-green-500" id="statResolved">-</p>
            <p class="text-sm text-gray-500">Resolved</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Crime Trends -->
        <div class="material-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Crime Trends</h3>
                <select id="trendMonths" class="px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-sm">
                    <option value="6">Last 6 Months</option>
                    <option value="12" selected>Last 12 Months</option>
                    <option value="24">Last 24 Months</option>
                </select>
            </div>
            <canvas id="trendChart" height="250"></canvas>
        </div>

        <!-- Crime by Type -->
        <div class="material-card p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Crime by Type</h3>
            <canvas id="typeChart" height="250"></canvas>
        </div>

        <!-- Crime by Barangay -->
        <div class="material-card p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Crime by Barangay</h3>
            <canvas id="barangayChart" height="250"></canvas>
        </div>

        <!-- Status Breakdown -->
        <div class="material-card p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Breakdown</h3>
            <canvas id="statusChart" height="250"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let trendChart, typeChart, barangayChart, statusChart;

    function loadCharts() {
        const months = document.getElementById('trendMonths').value;

        // Load trends
        fetch('{{ route("analytics.trends") }}?months=' + months)
            .then(r => r.json())
            .then(data => {
                if (trendChart) trendChart.destroy();
                trendChart = new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [{
                            label: 'Incidents',
                            data: data.map(d => d.total),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            });

        // Load by crime type
        fetch('{{ route("analytics.by-type") }}')
            .then(r => r.json())
            .then(data => {
                if (typeChart) typeChart.destroy();
                typeChart = new Chart(document.getElementById('typeChart'), {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.name),
                        datasets: [{
                            data: data.map(d => d.count),
                            backgroundColor: data.map(d => d.color),
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            });

        // Load by barangay
        fetch('{{ route("analytics.by-barangay") }}')
            .then(r => r.json())
            .then(data => {
                if (barangayChart) barangayChart.destroy();
                barangayChart = new Chart(document.getElementById('barangayChart'), {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.barangay),
                        datasets: [{
                            label: 'Incidents',
                            data: data.map(d => d.count),
                            backgroundColor: '#f97316',
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            });

        // Load status
        fetch('{{ route("analytics.status") }}')
            .then(r => r.json())
            .then(data => {
                const colors = {
                    'Report Received': '#f59e0b',
                    'CIRAS Recording': '#2563eb',
                    'For Summary Review': '#7c3aed',
                    'For Correction': '#dc2626',
                    'Data Stored': '#0891b2',
                    'IRF Printed': '#4f46e5',
                    'For IRF Signature': '#9333ea',
                    'Blotter Entered': '#0f766e',
                    'Compiled for UCPER': '#16a34a',
                    'Resolved': '#10b981',
                    'Closed': '#6b7280'
                };
                if (statusChart) statusChart.destroy();
                statusChart = new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.map(d => d.status),
                        datasets: [{
                            data: data.map(d => d.count),
                            backgroundColor: data.map(d => colors[d.status] || '#6b7280'),
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });

                // Update stats
                document.getElementById('statTotal').textContent = data.reduce((a, b) => a + b.count, 0);
                document.getElementById('statPending').textContent = data
                    .filter(d => ['Report Received', 'CIRAS Recording', 'For Summary Review', 'For Correction', 'Data Stored', 'IRF Printed', 'For IRF Signature', 'Blotter Entered', 'Compiled for UCPER'].includes(d.status))
                    .reduce((total, item) => total + item.count, 0);
                document.getElementById('statInvestigating').textContent = data
                    .filter(d => ['CIRAS Recording', 'For Summary Review', 'For Correction'].includes(d.status))
                    .reduce((total, item) => total + item.count, 0);
                document.getElementById('statResolved').textContent = data.find(d => d.status === 'Resolved')?.count || 0;
            });
    }

    loadCharts();
    document.getElementById('trendMonths').addEventListener('change', loadCharts);
});
</script>
@endpush

