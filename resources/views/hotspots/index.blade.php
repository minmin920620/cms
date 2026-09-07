@extends('layouts.app')

@section('title', 'Hotspots')
@section('page_title', 'Crime Hotspots')

@push('styles')
<style>
    #hotspotHeatMap {
        min-height: 430px;
        height: clamp(430px, 52vh, 620px);
        width: 100%;
        z-index: 1;
    }

    .hotspot-pulse-marker {
        border: 2px solid rgba(255, 255, 255, 0.95);
        border-radius: 9999px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.3);
    }

    .leaflet-heatmap-layer {
        opacity: 0.86;
    }

    .leaflet-popup-content-wrapper {
        border-radius: 10px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
    }

    .leaflet-popup-content {
        margin: 0;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="material-card p-5">
            <p class="text-sm font-medium text-slate-500">Total Incidents</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalIncidents }}</p>
        </div>
        <div class="material-card p-5">
            <p class="text-sm font-medium text-slate-500">Barangays With Incidents</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $barangaysWithIncidents }}</p>
        </div>
        <div class="material-card p-5">
            <p class="text-sm font-medium text-slate-500">Top Hotspot</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ $hotspots->first()->name ?? 'No data' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <div class="material-card overflow-hidden xl:col-span-3">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Hotspot Heat Map</h2>
                    <p class="mt-1 text-sm text-slate-500">Darker areas show stronger incident concentration across Koronadal City.</p>
                </div>
	                <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-600">
	                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
	                        <input type="checkbox" id="toggleHotspotMarkers" checked class="rounded border-slate-300 text-red-600 focus:ring-red-500">
	                        <span>Hotspot Markers</span>
	                    </label>
	                    <div class="flex items-center gap-2">
	                        <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
	                        <span>Low</span>
	                        <span class="h-3 w-3 rounded-full bg-amber-400"></span>
	                        <span>Medium</span>
	                        <span class="h-3 w-3 rounded-full bg-red-600"></span>
	                        <span>High</span>
	                    </div>
	                </div>
            </div>

            <div class="relative">
                <div id="hotspotHeatMap"></div>
                @if ($heatPoints->isEmpty())
                    <div class="absolute inset-0 z-[500] flex items-center justify-center bg-white/75 px-6 text-center backdrop-blur-sm">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">No mapped incidents yet</p>
                            <p class="mt-1 text-sm text-slate-500">Add latitude and longitude to crime incidents to display hotspot intensity.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="material-card p-5 xl:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Hotspot Chart</h2>
            <div class="mt-4">
                <canvas id="hotspotChart" height="280"></canvas>
            </div>
        </div>

        <div class="material-card xl:col-span-5">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Barangay Ranking</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse ($hotspots as $index => $barangay)
                    @php
                        $latestCrime = $barangay->latestCrime;
                        $percent = ($barangay->total_incidents / $maxIncidents) * 100;
                        $barColor = $barangay->total_incidents >= 7 ? 'bg-red-600' : ($barangay->total_incidents >= 3 ? 'bg-amber-500' : ($barangay->total_incidents > 0 ? 'bg-emerald-500' : 'bg-slate-300'));
                    @endphp

                    <div class="p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $barangay->total_incidents > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500' }} text-sm font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <h3 class="font-semibold text-slate-900">{{ $barangay->name }}</h3>
                                        <p class="text-sm text-slate-500">{{ $barangay->city }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-right">
                                <div>
                                    <p class="text-lg font-bold text-slate-900">{{ $barangay->total_incidents }}</p>
                                    <p class="text-xs text-slate-500">Total</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-amber-600">{{ $barangay->open_incidents }}</p>
                                    <p class="text-xs text-slate-500">Open</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-green-600">{{ $barangay->resolved_incidents }}</p>
                                    <p class="text-xs text-slate-500">Resolved</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full {{ $barColor }}" style="width: {{ $percent }}%"></div>
                        </div>

                        <div class="mt-3 flex flex-col gap-2 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                            <span>Risk share: {{ number_format($percent, 1) }}%</span>
                            @if ($latestCrime)
                                <a href="{{ route('crimes.show', $latestCrime) }}" class="font-medium text-red-600 hover:text-red-700">
                                    Latest: {{ $latestCrime->case_number }}
                                </a>
                            @else
                                <span>No latest incident</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <p class="text-lg font-semibold text-slate-900">No hotspot data yet</p>
                        <p class="mt-1 text-sm text-slate-500">Add crime incidents with barangay information to generate hotspot rankings.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartTarget = document.getElementById('hotspotChart');
    if (chartTarget) {
        new Chart(chartTarget, {
            type: 'bar',
            data: {
                labels: @json($hotspots->take(10)->pluck('name')),
                datasets: [{
                    label: 'Incidents',
                    data: @json($hotspots->take(10)->pluck('total_incidents')),
                    backgroundColor: '#dc2626',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    const mapTarget = document.getElementById('hotspotHeatMap');
    if (!mapTarget) return;

    if (typeof L === 'undefined') {
        mapTarget.innerHTML = `
            <div class="flex h-full min-h-[430px] items-center justify-center bg-slate-50 p-6 text-center">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Map library failed to load.</p>
                    <p class="mt-1 text-xs text-slate-500">Please check the internet connection and refresh the page.</p>
                </div>
            </div>
        `;

        return;
    }

    const koronadalBounds = L.latLngBounds([6.351757, 124.788841], [6.569806, 125.000435]);
    const map = L.map('hotspotHeatMap', {
        center: [6.504525, 124.891346],
        zoom: 12,
        minZoom: 11,
        zoomControl: false,
        maxBounds: koronadalBounds,
        maxBoundsViscosity: 0.9,
        attributionControl: false
    });

    map.fitBounds(koronadalBounds);
    L.control.zoom({ position: 'topright' }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        bounds: koronadalBounds,
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    const heatPoints = @json($heatPoints).filter((point) => (
        Number.isFinite(Number(point[0])) &&
        Number.isFinite(Number(point[1])) &&
        koronadalBounds.contains([Number(point[0]), Number(point[1])])
    ));
    const mapHotspots = @json($mapHotspots).filter((hotspot) => (
        Number.isFinite(Number(hotspot.lat)) &&
        Number.isFinite(Number(hotspot.lng)) &&
        koronadalBounds.contains([Number(hotspot.lat), Number(hotspot.lng)])
    ));

    if (L.heatLayer && heatPoints.length > 0) {
        L.heatLayer(heatPoints, {
            radius: 46,
            blur: 32,
            maxZoom: 17,
            minOpacity: 0.45,
            gradient: {
                0.15: '#1d4ed8',
                0.35: '#22c55e',
                0.55: '#facc15',
                0.75: '#f97316',
                1.0: '#dc2626'
            }
        }).addTo(map);
    }

	    const markerGroup = L.layerGroup().addTo(map);
	    const markerToggle = document.getElementById('toggleHotspotMarkers');

	    function renderHotspotMarkers() {
	        markerGroup.clearLayers();

	        if (markerToggle?.checked === false) {
	            return;
	        }

	        mapHotspots.forEach((hotspot) => {
	        const size = Math.max(18, Math.min(42, 18 + hotspot.density * 0.24));
	        const color = hotspot.total >= 7 ? '#dc2626' : (hotspot.total >= 3 ? '#f59e0b' : '#10b981');
	
	        const marker = L.circleMarker([hotspot.lat, hotspot.lng], {
            radius: size / 2,
            color: '#ffffff',
            weight: 2,
            fillColor: color,
            fillOpacity: 0.82,
            className: 'hotspot-pulse-marker'
        }).bindPopup(`
            <div class="min-w-[190px] p-4">
                <h3 class="text-sm font-bold text-slate-900">${hotspot.name}</h3>
                <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-lg font-bold text-slate-900">${hotspot.total}</p>
                        <p class="text-[11px] text-slate-500">Total</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-amber-600">${hotspot.open}</p>
                        <p class="text-[11px] text-slate-500">Open</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-emerald-600">${hotspot.resolved}</p>
                        <p class="text-[11px] text-slate-500">Resolved</p>
                    </div>
                </div>
                <p class="mt-3 text-xs font-medium text-slate-600">${hotspot.density}% of top hotspot intensity</p>
            </div>
        `);
	
	        marker.addTo(markerGroup);
	        });
	    }

	    renderHotspotMarkers();
	    markerToggle?.addEventListener('change', renderHotspotMarkers);
	
	    if (mapHotspots.length > 0 && markerGroup.getLayers().length > 0) {
	        map.fitBounds(markerGroup.getBounds().pad(0.18), { maxZoom: 15 });
	    }
});
</script>
@endpush
