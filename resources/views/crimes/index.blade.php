@extends('layouts.app')

@section('title', 'Crime Incidents – City of Koronadal')
@section('page_title', 'Crime Incidents')

@section('content')
    {{-- Action Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <p class="text-sm text-slate-500">Manage and track all reported crime incidents in Koronadal.</p>
        @if(auth()->user()->isAdmin() || auth()->user()->isPoliceOfficer())
            <a href="{{ route('crimes.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:from-blue-700 hover:to-blue-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Report New Incident
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="mb-6 material-card p-5">
        <form method="GET" action="{{ route('crimes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Case #, title..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Crime Type</label>
                <select name="crime_type_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Crime Types</option>
                    @foreach($crimeTypes as $type)
                        <option value="{{ $type->id }}" {{ request('crime_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Barangay</label>
                <select name="barangay_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay->id }}" {{ request('barangay_id') == $barangay->id ? 'selected' : '' }}>{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ \App\Models\Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                <a href="{{ route('crimes.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="material-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80">
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Barangay</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date Reported</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Time Reported</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date Committed</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Time Committed</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Stage of Felony</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Offense</th>
                        <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($crimes as $crime)
                        <tr class="transition hover:bg-slate-50/50">
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->barangay->name ?? 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->date_reported ? $crime->date_reported->format('M d, Y') : 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->time_reported ? $crime->time_reported->format('h:i A') : 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->date_occurred->format('M d, Y') }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->time_occurred ? $crime->time_occurred->format('h:i A') : 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->stage_of_felony ? ucfirst($crime->stage_of_felony) : 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->offenseType ? Str::limit($crime->offenseType->name, 42) : 'N/A' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $crime->status_label }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('crimes.show', $crime) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                </svg>
                    <p class="text-sm font-semibold text-slate-900">No crime incidents found</p>
                                <p class="mt-1 text-sm text-slate-500">Try adjusting your filters or report a new incident.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($crimes->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $crimes->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

