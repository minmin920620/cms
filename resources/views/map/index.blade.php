@extends('layouts.app')

@section('title', 'Crime Map')
@section('page_title', 'Geospatial Crime Map')

@push('styles')
<style>
    #crimeMap {
        height: 600px;
        z-index: 1;
    }
    #crimeMap.research-map-mode {
        background: #ffffff;
    }
    #crimeMap.research-map-mode .leaflet-tile-pane {
        filter: grayscale(85%) contrast(115%) brightness(70%);
    }
    #crimeMap.research-map-mode .barangay-label {
        display: none;
    }
    #crimeMap.research-map-mode .leaflet-control-zoom {
        display: none;
    }
    .research-map-frame {
        pointer-events: none;
        position: absolute;
        inset: 16px;
        z-index: 650;
        display: none;
        border: 1px solid rgba(15, 23, 42, 0.35);
    }
    #crimeMap.research-map-mode .research-map-frame,
    #crimeMap.research-map-mode .research-map-north,
    #crimeMap.research-map-mode .research-map-scale,
    #crimeMap.research-map-mode .research-map-caption {
        display: block;
    }
    .research-map-north {
        pointer-events: none;
        position: absolute;
        left: 28px;
        top: 26px;
        z-index: 660;
        display: none;
        color: #111827;
        font-family: Georgia, serif;
        font-size: 24px;
        line-height: 1;
    }
    .research-map-scale {
        pointer-events: none;
        position: absolute;
        right: 34px;
        bottom: 42px;
        z-index: 660;
        display: none;
        width: 116px;
        color: #111827;
        font-size: 10px;
        font-weight: 600;
        text-align: center;
    }
    .research-map-scale::before {
        content: "";
        display: block;
        height: 8px;
        margin-bottom: 3px;
        border: 1px solid #111827;
        background: linear-gradient(to right, #111827 0 25%, #ffffff 25% 50%, #111827 50% 75%, #ffffff 75% 100%);
    }
    .research-map-caption {
        pointer-events: none;
        position: absolute;
        left: 50%;
        bottom: 10px;
        z-index: 660;
        display: none;
        width: min(80%, 520px);
        transform: translateX(-50%);
        color: #111827;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 14px;
        font-style: italic;
        text-align: center;
        text-shadow: 0 1px 3px rgba(255, 255, 255, 0.95);
    }
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        padding: 0;
        overflow: hidden;
    }
    .leaflet-popup-content { margin: 0; }
    .leaflet-popup-content h4 { font-size: 15px; font-weight: 700; color: #1e293b; }
    .leaflet-popup-content p { font-size: 13px; margin: 2px 0; color: #475569; }
    .leaflet-popup-content p strong { color: #1e293b; }
    .leaflet-popup-tip { box-shadow: none; }
    .crime-marker {
        background: transparent !important;
        border: none !important;
    }
    .leaflet-heatmap-layer {
        mix-blend-mode: screen;
    }
    .barangay-label {
        font-size: 11px;
        font-weight: 600;
        color: #1e293b;
        text-shadow:
            0 0 3px rgba(255,255,255,1),
            0 0 3px rgba(255,255,255,1),
            0 0 6px rgba(255,255,255,1),
            0 0 6px rgba(255,255,255,1);
        pointer-events: none;
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        padding: 3px 0;
        color: #475569;
    }
    .legend-color {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1px solid rgba(0,0,0,0.1);
        flex-shrink: 0;
    }
    .legend-gradient {
        height: 10px;
        border-radius: 5px;
        background: linear-gradient(to right, #22c55e, #eab308, #f97316, #ef4444);
        margin: 4px 0;
    }
    .leaflet-control-zoom a {
        border-radius: 8px !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
        margin: 2px !important;
    }
    .leaflet-control-zoom a:hover {
        background: #f8fafc !important;
    }
    .filter-select {
        @apply w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700
               focus:ring-2 focus:ring-amber-500/30 focus:border-amber-400
               transition-all duration-150 appearance-none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
    }
    .filter-input {
        @apply w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700
               focus:ring-2 focus:ring-amber-500/30 focus:border-amber-400
               transition-all duration-150;
    }
    .stat-card {
        @apply px-4 py-3 rounded-lg border border-slate-100 bg-gradient-to-br from-slate-50 to-white;
    }
    .stat-value {
        @apply text-xl font-bold;
    }
    .leaflet-popup-content .popup-header {
        background: linear-gradient(135deg, #dc2626, #991b1b);
        padding: 14px 16px;
        color: white;
    }
    .leaflet-popup-content .popup-body {
        padding: 14px 16px;
    }
    .leaflet-popup-content .popup-body .info-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .leaflet-popup-content .popup-body .info-row:last-child {
        border-bottom: none;
    }
    .leaflet-popup-content .popup-body .info-label {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }
    .leaflet-popup-content .popup-body .info-value {
        color: #1e293b;
        font-size: 13px;
        font-weight: 600;
    }
    .bounds-toggle {
        @apply inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl border border-slate-200 
               bg-white text-sm font-medium text-slate-600 
               hover:bg-slate-50 hover:border-slate-300 
               transition-all duration-150 cursor-pointer select-none
               shadow-sm;
    }
    .bounds-toggle.active {
        @apply border-amber-400/40 bg-amber-50 text-amber-700;
    }
    #crimeMap .leaflet-map-pane {
        transform-origin: 50% 50%;
    }
    .map-tool-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: #ffffff;
        color: #475569;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        transition: all 150ms ease;
    }
    .map-tool-button:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #1e293b;
    }
    .rotation-slider {
        width: 10rem;
        height: 0.5rem;
        cursor: pointer;
        appearance: none;
        border-radius: 9999px;
        background: #e2e8f0;
    }
    .rotation-slider::-webkit-slider-thumb {
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 9999px;
        border: 3px solid #ffffff;
        background: #d97706;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.25);
    }
    .rotation-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border: 3px solid #ffffff;
        border-radius: 9999px;
        background: #d97706;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.25);
    }
    .leaflet-popup-content-wrapper,
    .leaflet-popup-tip {
        background: rgba(15, 18, 30, 0.95) !important;
        color: #e2e8f0 !important;
    }
    .leaflet-popup-content .popup-header {
        background: rgba(15, 18, 30, 0.95);
        color: #e2e8f0;
    }
    .leaflet-popup-content .popup-body {
        background: rgba(15, 18, 30, 0.95);
    }
    .leaflet-popup-content .popup-body .info-row {
        border-color: rgba(255, 255, 255, 0.06);
    }
    .leaflet-popup-content .popup-body .info-value,
    .leaflet-popup-content h4 {
        color: #e2e8f0;
    }
    .leaflet-tooltip.streetlight-label {
        padding: 4px 9px;
        border: 1px solid rgba(15, 23, 42, 0.18);
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.92);
        color: #0f172a;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.15;
        box-shadow:
            0 2px 8px rgba(15, 23, 42, 0.18),
            0 0 0 2px rgba(255, 255, 255, 0.8);
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.95);
    }
    .leaflet-tooltip.streetlight-label::before {
        border-right-color: rgba(255, 255, 255, 0.92);
    }
</style>
@endpush

@section('content')
<div class="flex flex-col xl:flex-row gap-6">
    <!-- Left Sidebar -->
    <div class="xl:w-80 space-y-4 order-2 xl:order-1">
        <!-- Filters Panel -->
        <div class="material-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-slate-800">Filter Map Data</h3>
                    <button id="clearFilters" class="ml-auto text-xs font-medium text-amber-600 hover:text-amber-700 transition-colors">Clear</button>
                </div>
            </div>
            <form id="mapFilters" class="p-5 space-y-3.5">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Crime Type</label>
                    <select id="filterCrimeType" class="filter-select">
                        <option value="">All Crime Types</option>
                        @foreach($crimeTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Barangay</label>
                    <select id="filterBarangay" class="filter-select">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Status</label>
                    <select id="filterStatus" class="filter-select">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ \App\Models\Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">From</label>
                        <input type="date" id="filterDateFrom" class="filter-input">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">To</label>
                        <input type="date" id="filterDateTo" class="filter-input">
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Card -->
        <div class="material-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-slate-800">Live Statistics</h3>
                </div>
            </div>
            <div class="p-5 space-y-3">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Incident Markers</span>
                        <span id="markerCount" class="stat-value text-blue-600">0</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full w-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full" style="width: 0%;"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Barangay Zones</span>
                        <span id="barangayCount" class="stat-value text-emerald-600">0</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full" style="width: 100%;"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Crime Density Index</span>
                        <span id="densityIndex" class="stat-value text-amber-600">0%</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div id="densityBar" class="h-full bg-gradient-to-r from-emerald-500 via-amber-500 to-red-500 rounded-full" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="material-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-slate-800">Map Legend</h3>
                </div>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Incident Status</p>
                    <div class="space-y-1.5">
                        <div class="legend-item"><span class="legend-color" style="background:#eab308;"></span> Report Received</div>
                        <div class="legend-item"><span class="legend-color" style="background:#2563eb;"></span> CIRAS / Review Flow</div>
                        <div class="legend-item"><span class="legend-color" style="background:#10b981;"></span> Completed</div>
                        <div class="legend-item"><span class="legend-color" style="background:#6b7280;"></span> Closed</div>
                    </div>
                </div>
                <hr class="border-slate-100">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Barangay Crime Density</p>
                    <div class="legend-gradient"></div>
                    <div class="flex justify-between text-[11px] text-slate-500 mt-1">
                        <span>Low (0)</span>
                        <span>Medium (1–3)</span>
                        <span>High (4–6)</span>
                        <span>Critical (7+)</span>
                    </div>
                </div>
                <hr class="border-slate-100">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Map Controls</p>
                    <ul class="space-y-1 text-xs text-slate-500">
                        <li>• Click a <strong class="text-slate-700">marker</strong> for incident details</li>
                        <li>• Click a <strong class="text-slate-700">barangay zone</strong> for area stats</li>
                        <li>• Use filters to refine displayed data</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Area -->
    <div class="flex-1 min-w-0 order-1 xl:order-2 space-y-3">
        <div class="material-card overflow-hidden">
            <div class="relative">
                <div id="crimeMap"></div>
                <div class="research-map-frame"></div>
                <div class="research-map-north">△</div>
                <div class="research-map-scale">0&nbsp;&nbsp;2.5&nbsp;&nbsp;5&nbsp;&nbsp;7.5&nbsp;&nbsp;10 km</div>
                <div class="research-map-caption">Map of Recorded Crime Incidents in Koronadal City.</div>
                <!-- Map Loading Overlay -->
                <div id="mapLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-[1000] hidden">
                    <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-xl shadow-lg border border-slate-200">
                        <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-slate-600">Loading map data...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Controls Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
	                <label class="bounds-toggle active" id="boundsToggleLabel">
	                    <input type="checkbox" id="toggleBoundaries" checked class="sr-only">
	                    <svg class="w-4 h-4 text-slate-400" id="boundsToggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
	                    </svg>
	                    <span>Barangay Boundaries</span>
	                </label>
	                @if(auth()->user()->isAdmin() || auth()->user()->isPoliceOfficer())
	                    <button type="button" id="plotModeToggle" class="bounds-toggle" title="Click on the map to plot a new incident">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Plot Incident</span>
                    </button>
                @endif
                <button type="button" id="researchModeToggle" class="bounds-toggle" title="Show a manuscript-style static plotting view">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M5 5v14h14V5M8 15l3-3 2 2 3-5" />
                    </svg>
                    <span>Research Plot</span>
                </button>
                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <button type="button" id="rotateLeft" class="map-tool-button" title="Rotate left 15 degrees" aria-label="Rotate map left">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v6h6M21 17a9 9 0 00-15-6.7L3 13" />
                        </svg>
                    </button>
                    <input type="range" id="rotationSlider" class="rotation-slider" min="0" max="359" value="0" aria-label="Map rotation">
                    <button type="button" id="rotateRight" class="map-tool-button" title="Rotate right 15 degrees" aria-label="Rotate map right">
                        <svg class="h-4 w-4 scale-x-[-1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v6h6M21 17a9 9 0 00-15-6.7L3 13" />
                        </svg>
                    </button>
                    <button type="button" id="resetRotation" class="map-tool-button" title="Reset north" aria-label="Reset map rotation">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l4 8H8l4-8zm0 18v-8" />
                        </svg>
                    </button>
                    <span id="rotationValue" class="min-w-12 text-right text-xs font-semibold text-slate-500">0&deg;</span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <span>Esri, OpenStreetMap &copy; contributors</span>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isPoliceOfficer())
<!-- Plot Incident Modal -->
<div id="plotModal" class="fixed inset-0 z-[2000] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="plotModalBackdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl border border-slate-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Plot New Incident</h3>
                        <p class="text-xs text-slate-500" id="plotCoords">Location selected</p>
                    </div>
                </div>
                <button type="button" id="plotModalClose" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="plotForm" class="p-6 space-y-4">
                <input type="hidden" id="plotLatitude" name="latitude">
                <input type="hidden" id="plotLongitude" name="longitude">
                <div id="plotLocationStatus" class="hidden rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-800"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="plotTitle" name="title" required maxlength="255"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150"
                            placeholder="Brief incident title...">
                        <p class="mt-1 text-xs text-red-500 hidden" id="plotTitleError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Crime Type <span class="text-red-500">*</span></label>
                        <select id="plotCrimeType" name="crime_type_id" required
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                            <option value="">Select type</option>
                            @foreach($crimeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-red-500 hidden" id="plotCrimeTypeError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Barangay <span class="text-red-500">*</span></label>
                        <select id="plotBarangay" name="barangay_id" required
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                            <option value="">Select barangay</option>
                            @foreach($barangays as $barangay)
                                <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-red-500 hidden" id="plotBarangayError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Date Committed <span class="text-red-500">*</span></label>
                        <input type="date" id="plotDateOccurred" name="date_occurred" required value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                        <p class="mt-1 text-xs text-red-500 hidden" id="plotDateError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Time Committed</label>
                        <input type="time" id="plotTimeOccurred" name="time_occurred"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Stage of Felony</label>
                        <select id="plotStage" name="stage_of_felony"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                            <option value="">Select stage</option>
                            <option value="consummated">Consummated</option>
                            <option value="frustrated">Frustrated</option>
                            <option value="attempted">Attempted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Offense</label>
                        <select id="plotOffense" name="offense_type_id"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150">
                            <option value="">Select offense</option>
                            @foreach($offenseTypes as $offenseType)
                                <option value="{{ $offenseType->id }}" data-crime-type-id="{{ $offenseType->crime_type_id }}">{{ $offenseType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Address / Location</label>
                        <input type="text" id="plotAddress" name="address" maxlength="500"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all duration-150"
                            placeholder="Street, landmark, or specific location...">
                    </div>

                </div>
            </form>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <button type="button" id="plotCancel"
                    class="px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="button" id="plotSubmit"
                    class="px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Report Incident</span>
                </button>
            </div>
            <!-- Submit Feedback -->
            <div id="plotFeedback" class="hidden px-6 py-3 border-t border-slate-100"></div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('crimeMap');

    if (typeof L === 'undefined') {
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="flex h-full min-h-[360px] items-center justify-center bg-slate-50 p-6 text-center">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Map library failed to load.</p>
                        <p class="mt-1 text-xs text-slate-500">Please check the internet connection and refresh the page.</p>
                    </div>
                </div>
            `;
        }

        return;
    }

    const koronadalCenter = [6.504525, 124.891346];
    const koronadalBounds = L.latLngBounds(
        [6.351757, 124.788841],
        [6.569806, 125.000435]
    );

    // Keep the map focused on Koronadal City.
    const map = L.map('crimeMap', {
        center: koronadalCenter,
        zoom: 12,
        minZoom: 11,
        maxBounds: koronadalBounds,
        maxBoundsViscosity: 0.9,
        zoomControl: false,
        attributionControl: false
    });
    map.fitBounds(koronadalBounds);

    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    const roadmapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        bounds: koronadalBounds,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const imageryLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        bounds: koronadalBounds,
        attribution: 'Tiles &copy; Esri'
    });

    const referenceLayer = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        bounds: koronadalBounds,
        attribution: ''
    });

    const markersLayer = L.layerGroup().addTo(map);
    const barangayLayer = L.layerGroup().addTo(map);
    const heatLayer = L.heatLayer ? L.heatLayer([], {
        radius: 34,
        blur: 24,
        maxZoom: 17,
        minOpacity: 0.35,
        gradient: {
            0.15: '#1d4ed8',
            0.35: '#22c55e',
            0.55: '#facc15',
            0.75: '#f97316',
            1.0: '#dc2626'
        }
    }).addTo(map) : null;

    const statusColors = {
        'pending': '#eab308',
        'ciras_recording': '#2563eb',
        'for_review': '#7c3aed',
        'for_correction': '#dc2626',
        'data_stored': '#0891b2',
        'irf_printed': '#4f46e5',
        'for_signature': '#9333ea',
        'blotter_entered': '#0f766e',
        'ucper_compiled': '#16a34a',
        'resolved': '#10b981',
        'closed': '#6b7280'
    };

    const statusLabels = {
        'pending': 'Report Received',
        'ciras_recording': 'CIRAS Recording',
        'for_review': 'For Summary Review',
        'for_correction': 'For Correction',
        'data_stored': 'Data Stored',
        'irf_printed': 'IRF Printed',
        'for_signature': 'For IRF Signature',
        'blotter_entered': 'Blotter Entered',
        'ucper_compiled': 'Compiled for UCPER',
        'resolved': 'Resolved',
        'closed': 'Closed'
    };
	    const rotationSlider = document.getElementById('rotationSlider');
	    const rotationValue = document.getElementById('rotationValue');
	    const rotateLeft = document.getElementById('rotateLeft');
	    const rotateRight = document.getElementById('rotateRight');
		    const resetRotation = document.getElementById('resetRotation');
		    const researchModeToggle = document.getElementById('researchModeToggle');
		    const isInvestigator = @json(auth()->user()->isInvestigator());
		    let researchMode = false;
    let mapBearing = 0;
    let rotationFrame = null;

    function normalizeBearing(value) {
        return ((Number(value) % 360) + 360) % 360;
    }

    function applyMapRotation(value) {
        mapBearing = normalizeBearing(value);

        if (typeof map.setBearing === 'function') {
            map.setBearing(mapBearing);
        } else {
            const mapPane = map.getPane('mapPane');
            const baseTransform = mapPane.style.transform.replace(/\srotate\([^)]+\)$/u, '');
            mapPane.style.transform = `${baseTransform} rotate(${mapBearing}deg)`;
        }

        if (rotationSlider) rotationSlider.value = Math.round(mapBearing);
        if (rotationValue) rotationValue.innerHTML = `${Math.round(mapBearing)}&deg;`;
    }

    function reapplyFallbackRotation() {
        if (typeof map.setBearing === 'function' || !mapBearing) return;

        if (rotationFrame) window.cancelAnimationFrame(rotationFrame);
        rotationFrame = window.requestAnimationFrame(() => {
            applyMapRotation(mapBearing);
            rotationFrame = null;
        });
    }

    rotationSlider?.addEventListener('input', event => applyMapRotation(event.target.value));
    rotateLeft?.addEventListener('click', () => applyMapRotation(mapBearing - 15));
    rotateRight?.addEventListener('click', () => applyMapRotation(mapBearing + 15));
    resetRotation?.addEventListener('click', () => applyMapRotation(0));
    map.on('move zoom viewreset moveend zoomend', reapplyFallbackRotation);
    setTimeout(() => applyMapRotation(mapBearing), 0);

    function getBarangayColor(density) {
        if (researchMode) return '#1f2933';
        if (density === 0) return '#22c55e';
        if (density <= 3) return '#eab308';
        if (density <= 6) return '#f97316';
        return '#ef4444';
    }

    function getBoundaryStyle(fillColor) {
        if (researchMode) {
            return {
                color: '#111827',
                weight: 1.1,
                opacity: 0.9,
                fillColor: fillColor,
                fillOpacity: 0.9
            };
        }

        return {
            color: '#334155',
            weight: 1.5,
            opacity: 0.6,
            fillColor: fillColor,
            fillOpacity: 0.2
        };
    }

    function getMarkerStyle(props) {
        const colorByStatus = {
            pending: '#ef4444',
            ciras_recording: '#2563eb',
            for_review: '#7c3aed',
            for_correction: '#dc2626',
            data_stored: '#0891b2',
            irf_printed: '#4f46e5',
            for_signature: '#9333ea',
            blotter_entered: '#0f766e',
            ucper_compiled: '#16a34a',
            resolved: '#22c55e',
            closed: '#22c55e'
        };
        const markerColor = colorByStatus[props.status] || props.crime_type_color || '#22c55e';

        if (researchMode) {
            return {
                radius: 6,
                color: '#ffffff',
                weight: 2,
                fillColor: markerColor,
                fillOpacity: 0.95
            };
        }

        return {
            radius: 9,
            color: '#ffffff',
            weight: 3,
            fillColor: markerColor,
            fillOpacity: 0.95
        };
    }

	    function setResearchMode(enabled) {
	        researchMode = enabled;
	        document.getElementById('crimeMap')?.classList.toggle('research-map-mode', researchMode);
        researchModeToggle?.classList.toggle('active', researchMode);
        imageryLayer.setOpacity(researchMode ? 0.65 : 1);
        referenceLayer.setOpacity(researchMode ? 0.15 : 1);
        if (heatLayer) {
            if (researchMode) {
                map.removeLayer(heatLayer);
            } else if (!map.hasLayer(heatLayer)) {
                map.addLayer(heatLayer);
            }
        }
	        loadBarangayBoundaries();
	        loadMarkers();
	    }

	    function boundaryPopupContent(props) {
	        return `
	            <div style="min-width: 230px;">
	                <div class="popup-header">
	                    <p style="font-size:11px;opacity:0.8;">Barangay Zone</p>
	                    <h4>${props.name}</h4>
	                    <p style="font-size:12px;opacity:0.9;margin-top:2px;">${props.city}</p>
	                    <p style="font-size:11px;opacity:0.75;margin-top:2px;">${props.boundary_source}</p>
	                </div>
	                <div class="popup-body">
	                    <div class="info-row">
	                        <span class="info-label">Total Incidents</span>
	                        <span class="info-value" style="color:#dc2626;">${props.total_crimes}</span>
	                    </div>
	                    <div class="info-row">
	                        <span class="info-label">Pending / Open</span>
	                        <span class="info-value" style="color:#eab308;">${props.pending_crimes}</span>
	                    </div>
	                    <div class="info-row">
	                        <span class="info-label">Resolved / Closed</span>
	                        <span class="info-value" style="color:#10b981;">${props.resolved_crimes}</span>
	                    </div>
	                    <div class="info-row">
	                        <span class="info-label">Crime Density</span>
	                        <span class="info-value">${props.crime_density}%</span>
	                    </div>
	                </div>
	            </div>
	        `;
	    }

	    function openBoundaryPopup(latlng, props) {
	        L.popup()
	            .setLatLng(latlng)
	            .setContent(boundaryPopupContent(props))
	            .openOn(map);
	    }

		    function currentMapFilterParams() {
		        return new URLSearchParams({
		            crime_type_id: document.getElementById('filterCrimeType').value,
		            barangay_id: document.getElementById('filterBarangay').value,
		            status: document.getElementById('filterStatus').value,
		            date_from: document.getElementById('filterDateFrom').value,
		            date_to: document.getElementById('filterDateTo').value,
		        });
		    }

		    function refreshMapData() {
		        loadBarangayBoundaries();
		        loadMarkers();
		    }

		    function loadBarangayBoundaries() {
		        document.getElementById('mapLoading')?.classList.remove('hidden');
		        barangayLayer.clearLayers();
	
		        fetch('{{ route("map.barangays") }}?' + currentMapFilterParams().toString())
            .then(response => {
                if (!response.ok) throw new Error('Unable to load barangay boundaries.');
                return response.json();
            })
	            .then(data => {
	                const features = Array.isArray(data.features) ? data.features : [];
	                const boundaryPropsById = new Map(features.map(feature => [String(feature.properties?.id), feature.properties]));
	                document.getElementById('barangayCount').textContent = features.length;

                let totalDensity = 0;
                let maxCrimes = 0;

                features.forEach(feature => {
                    const props = feature.properties;
                    const fillColor = getBarangayColor(props.total_crimes);

                    if (props.total_crimes > maxCrimes) maxCrimes = props.total_crimes;

                    const polygon = L.geoJSON(feature, {
                        style: getBoundaryStyle(fillColor)
                    }).addTo(barangayLayer);

                    polygon.on('mouseover', function() {
                        this.setStyle(researchMode
                            ? { fillOpacity: 0.95, weight: 1.8, opacity: 1 }
                            : { fillOpacity: 0.4, weight: 2.5, opacity: 0.9 });
                    });
                    polygon.on('mouseout', function() {
                        this.setStyle(getBoundaryStyle(fillColor));
                    });

	                    polygon.on('click', function(event) {
	                        fetch(`${detectBarangayUrl}?lat=${encodeURIComponent(event.latlng.lat)}&lng=${encodeURIComponent(event.latlng.lng)}`, {
	                            headers: { 'Accept': 'application/json' }
	                        })
	                            .then(response => response.ok ? response.json() : Promise.reject())
	                            .then(data => {
	                                const detectedProps = data.id ? boundaryPropsById.get(String(data.id)) : null;
	                                openBoundaryPopup(event.latlng, detectedProps || props);
	                            })
	                            .catch(() => openBoundaryPopup(event.latlng, props));
	                    });

                    totalDensity += parseFloat(props.crime_density);
                });

                // Update density index
                const avgDensity = features.length > 0 ? (totalDensity / features.length) : 0;
                document.getElementById('densityIndex').textContent = avgDensity.toFixed(1) + '%';
                document.getElementById('densityBar').style.width = Math.min(avgDensity, 100) + '%';

                document.getElementById('mapLoading')?.classList.add('hidden');
            })
            .catch(() => {
                document.getElementById('barangayCount').textContent = '0';
                document.getElementById('mapLoading')?.classList.add('hidden');
            });
    }

	    function loadMarkers() {
	        const params = currentMapFilterParams();

        fetch('{{ route("map.data") }}?' + params.toString())
            .then(response => {
                if (!response.ok) throw new Error('Unable to load incident markers.');
                return response.json();
            })
            .then(data => {
                markersLayer.clearLayers();
                const heatPoints = [];
                const searchTerm = (document.getElementById('filterSearch')?.value || '').trim().toLowerCase();
                let features = Array.isArray(data.features) ? data.features : [];
                if (searchTerm) {
                    features = features.filter(feature => {
                        const props = feature.properties || {};
                        return [
                            props.case_number,
                            props.title,
                            props.barangay,
                            props.address
                        ].some(value => String(value || '').toLowerCase().includes(searchTerm));
                    });
                }
                const count = features.length;
                document.getElementById('markerCount').textContent = count;
                const statusCounts = features.reduce((counts, feature) => {
                    const status = feature.properties?.status || '';
                    counts[status] = (counts[status] || 0) + 1;
                    return counts;
                }, {});
                const workingCount = (statusCounts.resolved || 0) + (statusCounts.closed || 0);
                const faultCount = (statusCounts.pending || 0) + (statusCounts.ciras_recording || 0) + (statusCounts.for_review || 0) + (statusCounts.for_correction || 0);
                const maintenanceCount = (statusCounts.data_stored || 0) + (statusCounts.irf_printed || 0) + (statusCounts.for_signature || 0) + (statusCounts.blotter_entered || 0) + (statusCounts.ucper_compiled || 0);
                const workingEl = document.getElementById('workingCount');
                const faultEl = document.getElementById('faultCount');
                const maintenanceEl = document.getElementById('maintenanceCount');
                if (workingEl) workingEl.textContent = workingCount;
                if (faultEl) faultEl.textContent = faultCount;
                if (maintenanceEl) maintenanceEl.textContent = maintenanceCount;

                // Update progress bar
                const progressBar = document.querySelector('.stat-card:first-child .rounded-full');
                if (progressBar) {
                    progressBar.style.width = Math.min((count / 50) * 100, 100) + '%';
                }

	                features.forEach(feature => {
	                    const props = feature.properties;
	                    const coords = feature.geometry.coordinates;
	                    const statusColor = statusColors[props.status] || '#6b7280';
	                    const markerLatLng = [coords[1], coords[0]];
	
	                    heatPoints.push([coords[1], coords[0], 0.85]);
	
	                    const marker = L.circleMarker(markerLatLng, getMarkerStyle(props))
	                        .addTo(markersLayer);

                    const statusLabel = statusLabels[props.status] || props.status;
                    const markerLabel = props.barangay || props.title || props.case_number;
                    marker.bindTooltip(markerLabel, {
                        permanent: true,
                        direction: 'right',
                        offset: [12, 0],
                        className: 'streetlight-label'
                    });

                    marker.bindPopup(`
                        <div style="min-width: 240px;">
                            <div class="popup-header">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <p style="font-size:11px;opacity:0.8;">Incident Report</p>
                                    <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:rgba(255,255,255,0.2);">${props.case_number}</span>
                                </div>
                                <h4 style="margin-top:4px;">${props.title}</h4>
                            </div>
                            <div class="popup-body">
                                <div class="info-row">
                                    <span class="info-label">Crime Type</span>
                                    <span class="info-value">${props.crime_type}</span>
                                </div>
                                ${props.offense ? `<div class="info-row">
                                    <span class="info-label">Offense</span>
                                    <span class="info-value">${props.offense}</span>
                                </div>` : ''}
                                <div class="info-row">
                                    <span class="info-label">Barangay</span>
                                    <span class="info-value">${props.barangay}</span>
                                </div>
                                ${props.date_reported ? `<div class="info-row">
                                    <span class="info-label">Date Reported</span>
                                    <span class="info-value">${props.date_reported}</span>
                                </div>` : ''}
                                <div class="info-row">
                                    <span class="info-label">Date Committed</span>
                                    <span class="info-value">${props.date_occurred}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status</span>
                                    <span class="info-value" style="color:${statusColor};text-transform:capitalize;">${statusLabel}</span>
                                </div>
                                ${props.address ? `<div class="info-row">
                                    <span class="info-label">Location</span>
                                    <span class="info-value">${props.address}</span>
                                </div>` : ''}
                                <div style="margin-top:12px;">
	                                    <a href="${props.url}" style="display:block;padding:8px 16px;background:#dc2626;color:white;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;text-align:center;">${isInvestigator ? 'Update Investigation' : 'View Full Details'}</a>
                                </div>
                            </div>
                        </div>
                    `);
                });

                if (heatLayer && !researchMode) {
                    heatLayer.setLatLngs(heatPoints);
                } else if (heatLayer) {
                    heatLayer.setLatLngs([]);
                }

	                if (features.length > 0) {
	                    const group = L.featureGroup(markersLayer.getLayers());
	                    map.fitBounds(group.getBounds().pad(0.1), { maxZoom: 16 });
	                } else if (!document.getElementById('filterBarangay').value) {
	                    map.fitBounds(koronadalBounds);
	                }
	            })
            .catch(() => {
                markersLayer.clearLayers();
                if (heatLayer) heatLayer.setLatLngs([]);
                document.getElementById('markerCount').textContent = '0';
                map.fitBounds(koronadalBounds);
            });
    }

    // Load barangay boundaries
    loadBarangayBoundaries();

    // Load markers on page load
    loadMarkers();

    // Reload markers on filter change
	    document.querySelectorAll('#mapFilters select, #mapFilters input').forEach(el => {
	        el.addEventListener('change', refreshMapData);
	    });

    // Clear filters
	    document.getElementById('clearFilters')?.addEventListener('click', function() {
	        document.querySelectorAll('#mapFilters select').forEach(el => el.value = '');
	        document.querySelectorAll('#mapFilters input').forEach(el => el.value = '');
	        refreshMapData();
	    });

    researchModeToggle?.addEventListener('click', function() {
        setResearchMode(!researchMode);
    });

    // Toggle barangay boundaries
	    document.getElementById('toggleBoundaries').addEventListener('change', function() {
	        if (this.checked) {
	            map.addLayer(barangayLayer);
	        } else {
	            map.removeLayer(barangayLayer);
	        }
	    });

    // ====== PLOT INCIDENT FEATURE ======
	    const canPlotIncident = @json(auth()->user()->isAdmin() || auth()->user()->isPoliceOfficer());
	    if (!canPlotIncident) {
	        return;
	    }

    const plotModeToggle = document.getElementById('plotModeToggle');
    const plotModal = document.getElementById('plotModal');
    const plotModalBackdrop = document.getElementById('plotModalBackdrop');
    const plotModalClose = document.getElementById('plotModalClose');
    const plotCancel = document.getElementById('plotCancel');
    const plotSubmit = document.getElementById('plotSubmit');
    const plotForm = document.getElementById('plotForm');
	    const plotFeedback = document.getElementById('plotFeedback');
	    const plotCoords = document.getElementById('plotCoords');
	    const plotLatInput = document.getElementById('plotLatitude');
	    const plotLngInput = document.getElementById('plotLongitude');
	    const plotLocationStatus = document.getElementById('plotLocationStatus');
	    const plotCrimeTypeSelect = document.getElementById('plotCrimeType');
	    const plotOffenseSelect = document.getElementById('plotOffense');
	    const plotOffenseOptions = plotOffenseSelect ? Array.from(plotOffenseSelect.options) : [];
	    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let plotMarker = null;
    let plotModeActive = false;
    let plotDetectionRequestId = 0;
    const plotApiUrl = '{{ route("map.plot-incident") }}';
    const detectBarangayUrl = '{{ route("map.detect-barangay") }}';

    function showPlotError(id, message) {
        const errorEl = document.getElementById(id);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    }

    function clearPlotErrors() {
        document.querySelectorAll('#plotForm [id$="Error"]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

	    function resetPlotForm() {
	        plotForm.reset();
	        syncPlotOffenseToCrimeType(false);
	        clearPlotErrors();
        plotFeedback.classList.add('hidden');
        plotFeedback.innerHTML = '';
        plotLocationStatus.classList.add('hidden');
        plotLocationStatus.textContent = '';
        plotSubmit.disabled = false;
        plotSubmit.querySelector('span').textContent = 'Report Incident';
        const timestamp = localPlotTimestamp();
        document.getElementById('plotDateOccurred').value = timestamp.date;
        document.getElementById('plotTimeOccurred').value = timestamp.time;
    }

	    function showPlotFeedback(type, message) {
        plotFeedback.classList.remove('hidden');
        const isError = type === 'error';
        plotFeedback.className = `px-6 py-3 border-t border-slate-100 ${isError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`;
        plotFeedback.innerHTML = `<div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 shrink-0 ${isError ? 'text-red-500' : 'text-emerald-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isError ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span></div>`;
	    }

	    function syncPlotOffenseToCrimeType(shouldAutoSelect = true) {
	        if (!plotCrimeTypeSelect || !plotOffenseSelect) return;

	        const selectedCrimeType = plotCrimeTypeSelect.value;
	        const currentOffense = plotOffenseSelect.value;
	        let firstMatchingValue = '';
	        let currentStillVisible = false;

	        plotOffenseOptions.forEach((option) => {
	            if (option.value === '') {
	                option.hidden = false;
	                option.disabled = false;
	                return;
	            }

	            const matches = selectedCrimeType === '' || option.dataset.crimeTypeId === selectedCrimeType;
	            option.hidden = !matches;
	            option.disabled = !matches;

	            if (matches && firstMatchingValue === '') {
	                firstMatchingValue = option.value;
	            }

	            if (matches && option.value === currentOffense) {
	                currentStillVisible = true;
	            }
	        });

	        if (!currentStillVisible) {
	            plotOffenseSelect.value = shouldAutoSelect ? firstMatchingValue : '';
	        }
	    }

    function padPlotTimePart(value) {
        return String(value).padStart(2, '0');
    }

    function localPlotTimestamp() {
        const now = new Date();

        return {
            date: `${now.getFullYear()}-${padPlotTimePart(now.getMonth() + 1)}-${padPlotTimePart(now.getDate())}`,
            time: `${padPlotTimePart(now.getHours())}:${padPlotTimePart(now.getMinutes())}`,
            label: now.toLocaleString([], {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })
        };
    }

    function showPlotLocationStatus(message, tone = 'info') {
        const classes = tone === 'error'
            ? 'rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800'
            : 'rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-800';

        plotLocationStatus.className = classes;
        plotLocationStatus.textContent = message;
        plotLocationStatus.classList.remove('hidden');
    }

    function detectPlotBarangay(lat, lng, timestamp) {
        const requestId = ++plotDetectionRequestId;
        const barangaySelect = document.getElementById('plotBarangay');
        const addressInput = document.getElementById('plotAddress');

        showPlotLocationStatus(`Detecting barangay... Timestamp: ${timestamp.label}`);

        fetch(`${detectBarangayUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => response.ok ? response.json() : Promise.reject())
            .then(data => {
                if (requestId !== plotDetectionRequestId) return;

                if (data.success && data.id) {
                    barangaySelect.value = String(data.id);
                    const selectedName = barangaySelect.options[barangaySelect.selectedIndex]?.text || data.barangay;

                    if (addressInput && !addressInput.value.trim()) {
                        addressInput.value = `${selectedName}, Koronadal City`;
                    }

                    const sourceLabel = data.method === 'nearest_distance' ? 'Nearest mapped area' : 'Detected area';
                    showPlotLocationStatus(`${sourceLabel}: ${selectedName}. Timestamp: ${timestamp.label}`);
                    return;
                }

                showPlotLocationStatus(`Pin timestamp: ${timestamp.label}. Barangay was not detected; please select it manually.`, 'error');
            })
            .catch(() => {
                if (requestId !== plotDetectionRequestId) return;
                showPlotLocationStatus(`Pin timestamp: ${timestamp.label}. Barangay was not detected; please select it manually.`, 'error');
            });
    }

    function applyPlotCoordinates(lat, lng) {
        plotLatInput.value = lat.toFixed(7);
        plotLngInput.value = lng.toFixed(7);
        plotCoords.textContent = `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`;
    }

    function openPlotModal(lat, lng) {
        resetPlotForm();
        applyPlotCoordinates(lat, lng);
        detectPlotBarangay(lat, lng, localPlotTimestamp());
        plotModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePlotModal() {
        plotModal.classList.add('hidden');
        document.body.style.overflow = '';
        if (plotMarker) {
            map.removeLayer(plotMarker);
            plotMarker = null;
        }
        plotModeToggle?.classList.remove('active');
        plotModeActive = false;
        plotDetectionRequestId++;
        document.getElementById('crimeMap').style.cursor = '';
    }

    function addPlotMarker(lat, lng) {
        if (plotMarker) map.removeLayer(plotMarker);
        const icon = L.divIcon({
            className: 'plot-marker-icon',
            html: `<div style="width:32px;height:32px;background:#dc2626;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><circle cx="12" cy="12" r="6"/></svg></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });
        plotMarker = L.marker([lat, lng], { icon: icon, draggable: true, zIndexOffset: 10000 }).addTo(map);
        plotMarker.on('dragend', function() {
            const pos = plotMarker.getLatLng();
            applyPlotCoordinates(pos.lat, pos.lng);
            const timestamp = localPlotTimestamp();
            document.getElementById('plotDateOccurred').value = timestamp.date;
            document.getElementById('plotTimeOccurred').value = timestamp.time;
            detectPlotBarangay(pos.lat, pos.lng, timestamp);
        });
        map.setView([lat, lng], Math.max(map.getZoom(), 15));
    }

    // Plot mode toggle
    plotModeToggle?.addEventListener('click', function() {
        plotModeActive = !plotModeActive;
        this.classList.toggle('active');
        document.getElementById('crimeMap').style.cursor = plotModeActive ? 'crosshair' : '';
        if (!plotModeActive && plotMarker) {
            map.removeLayer(plotMarker);
            plotMarker = null;
        }
    });

    // Map click handler for plotting
    map.on('click', function(e) {
        if (!plotModeActive) return;
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        addPlotMarker(lat, lng);
        openPlotModal(lat, lng);
        plotModeActive = false;
        plotModeToggle?.classList.remove('active');
        document.getElementById('crimeMap').style.cursor = '';
    });

    // Close modal handlers
	    [plotModalBackdrop, plotModalClose, plotCancel].forEach(el => {
	        el?.addEventListener('click', closePlotModal);
	    });

	    plotCrimeTypeSelect?.addEventListener('change', () => syncPlotOffenseToCrimeType(true));
	    syncPlotOffenseToCrimeType(false);

    // Submit plot form
    plotSubmit?.addEventListener('click', function() {
        clearPlotErrors();
        plotFeedback.classList.add('hidden');

        const title = document.getElementById('plotTitle').value.trim();
        const crimeType = document.getElementById('plotCrimeType').value;
        const barangay = document.getElementById('plotBarangay').value;
        const dateOccurred = document.getElementById('plotDateOccurred').value;

        let hasError = false;
        if (!title) { showPlotError('plotTitleError', 'Title is required.'); hasError = true; }
        if (!crimeType) { showPlotError('plotCrimeTypeError', 'Crime type is required.'); hasError = true; }
        if (!barangay) { showPlotError('plotBarangayError', 'Barangay is required.'); hasError = true; }
        if (!dateOccurred) { showPlotError('plotDateError', 'Date is required.'); hasError = true; }

        if (hasError) return;

        const formData = new FormData(plotForm);
        formData.append('_token', csrfToken);

        plotSubmit.disabled = true;
        plotSubmit.querySelector('span').textContent = 'Submitting...';

        fetch(plotApiUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showPlotFeedback('success', `Incident #${data.crime.case_number} reported successfully!`);
                setTimeout(() => {
                    closePlotModal();
                    loadMarkers(); // Refresh map markers
                }, 1200);
            } else {
                plotSubmit.disabled = false;
                plotSubmit.querySelector('span').textContent = 'Report Incident';
                if (data.errors) {
                    Object.entries(data.errors).forEach(([key, msgs]) => {
                        const errorId = 'plot' + key.replace(/_([a-z])/g, (_, c) => c.toUpperCase()).replace(/^./, s => s.toUpperCase()) + 'Error';
                        showPlotError(errorId, msgs[0]);
                    });
                } else {
                    showPlotFeedback('error', data.message || 'An error occurred.');
                }
            }
        })
        .catch(err => {
            plotSubmit.disabled = false;
            plotSubmit.querySelector('span').textContent = 'Report Incident';
            showPlotFeedback('error', 'Network error. Please try again.');
        });
    });

    // Allow Enter key to submit
    plotForm?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            plotSubmit?.click();
        }
    });
});
</script>
@endpush

