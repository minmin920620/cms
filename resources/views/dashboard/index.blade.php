@extends('layouts.app')

@section('title', 'Dashboard – City of Tupi')
@section('page_title', 'Dashboard')

@section('content')
    @if(Auth::user()->isInvestigator())
        <div class="material-card mb-8 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Assigned Investigations</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-foreground">{{ $assignedInvestigationCount }} active case{{ $assignedInvestigationCount === 1 ? '' : 's' }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Cases assigned directly to you and still pending or under investigation.</p>
                </div>
                <a href="{{ route('crimes.index') }}" class="inline-flex w-fit items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                    View Cases
                </a>
            </div>

            @if($assignedInvestigations->count() > 0)
                <div class="mt-5 divide-y divide-slate-200 rounded-lg border border-slate-200">
                    @foreach($assignedInvestigations as $crime)
                        <a href="{{ route('crimes.show', $crime) }}" class="flex flex-col gap-2 p-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $crime->case_number }} - {{ $crime->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $crime->barangay->name ?? 'Unknown barangay' }} · {{ $crime->crimeType->name ?? 'Unknown type' }}</p>
                            </div>
                            <span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold capitalize text-amber-700">
                                {{ str_replace('_', ' ', $crime->status) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                    No active investigations are assigned to you right now.
                </div>
            @endif
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="material-card material-card-hover p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Incidents</p>
                    <p class="mt-2 text-3xl font-extrabold text-foreground">{{ $totalCrimes }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-600 shadow-lg shadow-blue-600/20">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">All recorded crime incidents</p>
        </div>

        <div class="material-card material-card-hover p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Today</p>
                    <p class="mt-2 text-3xl font-extrabold text-foreground">{{ $crimesToday }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-amber-500 shadow-lg shadow-amber-500/20">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">Incidents reported today</p>
        </div>

        <div class="material-card material-card-hover p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">This Month</p>
                    <p class="mt-2 text-3xl font-extrabold text-foreground">{{ $crimesThisMonth }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-600 shadow-lg shadow-emerald-600/20">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">Monthly incident count</p>
        </div>

        <div class="material-card material-card-hover p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Pending</p>
                    <p class="mt-2 text-3xl font-extrabold text-foreground">{{ $pendingCrimes }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-red-600 shadow-lg shadow-red-600/20">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">Awaiting action</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        @foreach([
            ['label' => 'Unassigned Cases', 'value' => $taskIndicators['unassigned'], 'href' => route('crimes.index', ['unassigned' => 1])],
            ['label' => 'Pending Review', 'value' => $taskIndicators['pending_review'], 'href' => route('crimes.index', ['status' => \App\Models\Crime::STATUS_FOR_REVIEW])],
            ['label' => 'For Correction', 'value' => $taskIndicators['for_correction'], 'href' => route('crimes.index', ['status' => \App\Models\Crime::STATUS_FOR_CORRECTION])],
            ['label' => 'No Coordinates', 'value' => $taskIndicators['without_coordinates'], 'href' => route('crimes.index', ['missing_coordinates' => 1])],
        ] as $indicator)
            <a href="{{ $indicator['href'] }}" class="material-card material-card-hover block p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $indicator['label'] }}</p>
                <p class="mt-2 text-2xl font-extrabold text-foreground">{{ $indicator['value'] }}</p>
                <p class="mt-2 text-xs text-muted-foreground">Needs attention</p>
            </a>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Crime by Type (Pie) --}}
        <div class="material-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-foreground">Crime by Type</h3>
                <span class="material-chip text-blue-600">Distribution</span>
            </div>
            <canvas id="crimeByTypeChart" height="260"></canvas>
        </div>

        {{-- Status Breakdown (Doughnut) --}}
        <div class="material-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-foreground">Status Breakdown</h3>
                <span class="material-chip text-amber-600">Overview</span>
            </div>
            <canvas id="statusBreakdownChart" height="260"></canvas>
        </div>
    </div>

    {{-- Second Row: Hotspots + Monthly Trend --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Hotspot Summary --}}
        <div class="material-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-foreground">Top Barangay Hotspots</h3>
                <div class="flex items-center gap-2">
                    <span class="material-chip text-red-600">High Risk</span>
                    @unless(Auth::user()->isLgu())
                        <a href="{{ route('hotspots.index') }}" class="material-chip hover:bg-accent transition">View All</a>
                    @endunless
                </div>
            </div>
            @if($hotspots->count() > 0)
                <div class="space-y-4">
                    @foreach($hotspots as $hotspot)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-card-foreground">{{ $hotspot['barangay'] }}</span>
                                <span class="text-sm font-bold text-foreground">{{ $hotspot['count'] }}</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-muted">
                                <div class="h-2.5 rounded-full bg-primary transition-all duration-500" style="width: {{ ($hotspot['count'] / $hotspots->max('count')) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-muted-foreground">
                    <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-sm font-medium">No data available</p>
                    <p class="text-xs">Crime data will appear here once recorded.</p>
                </div>
            @endif
        </div>

        {{-- Monthly Trend Line --}}
        <div class="material-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-foreground">Monthly Trend</h3>
                <span class="material-chip text-emerald-600">6 Months</span>
            </div>
            <canvas id="monthlyTrendChart" height="260"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Crime by Type Pie Chart
    const crimeByTypeCtx = document.getElementById('crimeByTypeChart');
    if (crimeByTypeCtx) {
        new Chart(crimeByTypeCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($crimeByType->pluck('name')) !!},
                datasets: [{
                    data: {!! json_encode($crimeByType->pluck('count')) !!},
                    backgroundColor: {!! json_encode($crimeByType->pluck('color')) !!},
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, font: { size: 11, family: 'Inter' } }
                    }
                },
                cutout: '55%',
            }
        });
    }

    // Status Breakdown Doughnut Chart
    const statusCtx = document.getElementById('statusBreakdownChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($statusBreakdown->pluck('status')) !!},
                datasets: [{
                    data: {!! json_encode($statusBreakdown->pluck('count')) !!},
                    backgroundColor: {!! json_encode($statusChartColors) !!},
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 11, family: 'Inter' } } }
                },
                cutout: '65%',
            }
        });
    }

    // Monthly Trend Chart
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyCrimes->pluck('month')) !!},
                datasets: [{
                    label: 'Incidents',
                    data: {!! json_encode($monthlyCrimes->pluck('total')) !!},
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1e293b', titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter' } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { ticks: { font: { family: 'Inter' } }, grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush

