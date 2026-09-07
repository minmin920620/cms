@extends('layouts.app')

@section('title', 'Reports')
@section('page_title', 'Generate Reports')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Filter Card -->
    <div class="lg:col-span-1">
        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <form method="GET" action="{{ route('reports.preview') }}" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Crime Type</label>
                    <select name="crime_type_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm">
                        <option value="">All Crime Types</option>
                        @foreach($crimeTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                    <select name="barangay_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ \App\Models\Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm">
                </div>
                <button type="submit" class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                    Generate Report
                </button>
            </form>
        </div>
    </div>

    <!-- Info Card -->
    <div class="lg:col-span-2">
        <div class="material-card p-6">
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Generate Custom Reports</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    Use the filters on the left to generate customized crime reports. You can filter by crime type, barangay, status, and date range.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-4">
                    <div class="px-4 py-3 bg-blue-50 rounded-lg">
                        <p class="text-sm font-medium text-blue-700">PDF Export</p>
                        <p class="text-xs text-blue-500">Download ready</p>
                    </div>
                    <div class="px-4 py-3 bg-green-50 rounded-lg">
                        <p class="text-sm font-medium text-green-700">Print Reports</p>
                        <p class="text-xs text-green-500">Use browser print</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

