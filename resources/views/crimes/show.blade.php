@extends('layouts.app')

@section('title', $crime->case_number)
@section('page_title', 'Incident Details: ' . $crime->case_number)

@push('styles')
<style>
    .investigation-findings ul {
        list-style: disc;
        margin: 0 0 .75rem 1.25rem;
        padding-left: 1rem;
    }

    .investigation-findings li {
        display: list-item;
        margin-bottom: .25rem;
    }

    .investigation-findings p {
        margin-bottom: .5rem;
    }
</style>
@endpush

@section('content')
@php
    $canEditCrime = Auth::user()->isAdmin()
        || (Auth::user()->isPoliceOfficer() && $crime->reported_by === Auth::id())
        || (Auth::user()->isInvestigator() && $crime->assigned_officer === Auth::id());
    $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Info Card -->
        <div class="material-card p-6">
            <div class="flex items-start justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $crime->title }}</h3>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'ciras_recording' => 'bg-blue-100 text-blue-800',
                        'for_review' => 'bg-purple-100 text-purple-800',
                        'for_correction' => 'bg-red-100 text-red-800',
                        'data_stored' => 'bg-cyan-100 text-cyan-800',
                        'irf_printed' => 'bg-indigo-100 text-indigo-800',
                        'for_signature' => 'bg-fuchsia-100 text-fuchsia-800',
                        'blotter_entered' => 'bg-teal-100 text-teal-800',
                        'ucper_compiled' => 'bg-emerald-100 text-emerald-800',
                        'resolved' => 'bg-green-100 text-green-800',
                        'closed' => 'bg-gray-100 text-gray-800',
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$crime->status] ?? 'bg-gray-100' }}">
                    {{ $crime->status_label }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Case Number</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-1">{{ $crime->case_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Crime Type</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: {{ $crime->crimeType->color ?? '#6b7280' }}20; color: {{ $crime->crimeType->color ?? '#6b7280' }}">
                            {{ $crime->crimeType->name ?? 'N/A' }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Offense</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->offenseType->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stage of Felony</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->stage_of_felony ? ucfirst($crime->stage_of_felony) : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date Reported</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        {{ $crime->date_reported ? $crime->date_reported->format('F d, Y') : 'N/A' }}
                        @if($crime->time_reported) at {{ $crime->time_reported->format('h:i A') }} @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date Committed</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->date_occurred->format('F d, Y') }} @if($crime->time_occurred) at {{ $crime->time_occurred->format('h:i A') }} @endif</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Barangay</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->barangay->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->address ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->latitude ? $crime->latitude . ', ' . $crime->longitude : 'N/A' }}</p>
                </div>
            </div>

            @if($crime->status_notes)
                <div class="p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-1">Status Notes</p>
                    <p class="text-sm text-blue-800">{{ $crime->status_notes }}</p>
                </div>
            @endif
        </div>

        <!-- Investigation Findings Card -->
        <div class="material-card p-6">
            <div class="flex items-start justify-between gap-4 mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Investigation Findings</h3>
                @if($crime->findings_recorded_at)
                    <span class="text-xs font-medium text-gray-500">{{ $displayTime($crime->findings_recorded_at) }}</span>
                @endif
            </div>
	            <div class="investigation-findings prose prose-sm max-w-none text-gray-700 leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1 [&_p]:mb-2">
                {!! $crime->formatted_investigation_findings !!}
            </div>
        </div>

        <!-- Evidence Card -->
        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Evidence ({{ $crime->evidence->count() }})</h3>
            @if($crime->evidence->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($crime->evidence as $item)
                        <div class="relative group">
                            <span class="absolute left-2 top-2 z-10 rounded-full bg-white/95 px-2 py-0.5 text-[11px] font-semibold text-gray-700 shadow">{{ $item->category_label }}</span>
                            @if(in_array($item->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                <img src="{{ route('evidence.show', $item) }}" alt="{{ $item->file_name }}" class="w-full h-32 object-cover rounded-lg border border-gray-200">
                            @else
                                <div class="w-full h-32 flex items-center justify-center bg-gray-100 rounded-lg border border-gray-200">
                                    <div class="text-center">
                                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-xs text-gray-500">{{ $item->file_name }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition rounded-lg flex items-center justify-center">
                                <a href="{{ route('evidence.show', $item) }}" target="_blank" class="text-white opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No evidence uploaded for this incident.</p>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Personnel Card -->
        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Personnel</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reported By</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->reporter->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $crime->reporter->role ?? '') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Investigator</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $crime->assignedOfficer->name ?? 'Unassigned' }}</p>
                    @if($crime->assignedOfficer)
                        <p class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $crime->assignedOfficer->role) }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($crime->approval_status && $crime->approval_status !== 'none')
            <div class="material-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Supervisor Approval</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold">Status:</span> {{ ucfirst($crime->approval_status) }}</p>
                    <p><span class="font-semibold">Requested Final Status:</span> {{ \App\Models\Crime::STATUS_LABELS[$crime->approval_requested_status] ?? 'N/A' }}</p>
                    <p><span class="font-semibold">Requested By:</span> {{ $crime->approvalRequester->name ?? 'N/A' }}</p>
                    <p><span class="font-semibold">Requested At:</span> {{ $displayTime($crime->approval_requested_at) ?? 'N/A' }}</p>
                    @if($crime->approval_notes)
                        <p class="whitespace-pre-line"><span class="font-semibold">Notes:</span> {{ $crime->approval_notes }}</p>
                    @endif
                </div>

                @if(Auth::user()->isAdmin() && $crime->approval_status === 'pending')
                    <form method="POST" action="{{ route('crimes.approval', $crime) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="approval_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Approval remarks"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <button name="decision" value="approved" class="rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white">Approve</button>
                            <button name="decision" value="rejected" class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Reject</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="space-y-3">
	                @if($canEditCrime)
	                    <a href="{{ route('crimes.edit', $crime) }}" class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
	                        {{ Auth::user()->isInvestigator() ? 'Update Investigation' : 'Edit Incident' }}
	                    </a>
                    <a href="{{ route('crimes.print', $crime) }}" target="_blank" class="w-full flex items-center justify-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition">
                        Print Case File
                    </a>

                @endif

                @if(Auth::user()->isAdmin())
                    <form method="POST" action="{{ route('crimes.destroy', $crime) }}" onsubmit="return confirm('Archive this incident? It will be hidden from active records but kept for future reference.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition">
                            Archive Incident
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Assignment History</h3>
            <div class="space-y-4">
                @forelse($crime->assignmentHistories as $assignment)
                    <div class="border-l-2 border-blue-200 pl-3">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $assignment->oldUser->name ?? 'Unassigned' }} to {{ $assignment->newUser->name ?? 'Unassigned' }}
                        </p>
                        <p class="text-xs text-gray-500">Changed by {{ $assignment->changedBy->name ?? 'System' }} on {{ $displayTime($assignment->created_at) }}</p>
                        @if($assignment->remarks)
                            <p class="mt-1 text-xs text-gray-700">{{ $assignment->remarks }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No assignment changes recorded yet.</p>
                @endforelse
            </div>
        </div>

        @if($canEditCrime && $crime->next_workflow_status)
            <div class="material-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Workflow Update</h3>
                <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                    <p class="text-slate-600">Current Status</p>
                    <p class="font-semibold text-slate-900">{{ $crime->status_label }}</p>
                    <p class="mt-3 text-slate-600">Next Step</p>
                    <p class="font-semibold text-slate-900">{{ \App\Models\Crime::STATUS_LABELS[$crime->next_workflow_status] ?? 'Next Step' }}</p>
                </div>
                <form method="POST" action="{{ route('crimes.update-status', $crime) }}">
                    @csrf
                    <input type="hidden" name="status" value="{{ $crime->next_workflow_status }}">
                    <label for="status_notes_quick" class="block text-sm font-medium text-gray-700 mb-1">Workflow Remarks <span class="text-red-500">*</span></label>
                    <textarea id="status_notes_quick" name="status_notes" rows="3" required maxlength="500" placeholder="Example: CIRAS encoding completed, ready for review."
                        class="mb-3 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-500 focus:ring-2 focus:ring-green-100">{{ old('status_notes') }}</textarea>
                    @error('status_notes') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" onclick="return confirm('Move this incident to {{ \App\Models\Crime::STATUS_LABELS[$crime->next_workflow_status] ?? 'the next step' }}?')" class="w-full flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                        Move to {{ \App\Models\Crime::STATUS_LABELS[$crime->next_workflow_status] ?? 'Next Step' }}
                    </button>
                </form>
            </div>
        @endif

        <!-- Crime Mapping Workflow History -->
        <div class="material-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Crime Workflow History</h3>
            <div class="space-y-4">
                @forelse($crime->statusHistories->sortBy('created_at') as $history)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full" style="background-color: {{ \App\Models\Crime::STATUS_COLORS[$history->new_status] ?? '#6b7280' }}"></div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $history->new_status_label }}</p>
                            <p class="text-xs text-gray-500">
                                @if($history->old_status)
                                    Changed from {{ $history->old_status_label }} by {{ $history->changedBy->name ?? 'System' }}
                                @else
                                    Recorded by {{ $history->changedBy->name ?? 'System' }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ $displayTime($history->created_at) }}</p>
                            @if($history->remarks)
                                <p class="mt-1 text-xs text-gray-700 whitespace-pre-line">{{ $history->remarks }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-yellow-400"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Reported</p>
                            <p class="text-xs text-gray-500">{{ $displayTime($crime->created_at) }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

