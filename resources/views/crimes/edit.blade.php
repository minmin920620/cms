@extends('layouts.app')

@section('title', 'Edit Incident')
@section('page_title', 'Edit Incident: ' . $crime->case_number)
@php($isInvestigatorUpdate = auth()->user()->isInvestigator())

@push('styles')
<style>
	    #incidentLocationMap {
	        height: 360px;
	        min-height: 360px;
	        width: 100%;
	        z-index: 1;
	    }
	    [data-findings-editor] ul {
	        list-style: disc;
	        margin: 0 0 .5rem 1.25rem;
	        padding: 0;
	    }
	    [data-findings-editor] li {
	        display: list-item;
	        margin: 0 0 .25rem;
	    }
	    [data-findings-editor] p {
	        margin: 0 0 .5rem;
	    }
	    [data-findings-editor]:empty::before {
	        color: #9ca3af;
	        content: attr(data-placeholder);
	    }
	</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="material-card p-6">
	        <form method="POST" action="{{ route('crimes.update', $crime) }}" enctype="multipart/form-data" class="space-y-6">
	            @csrf
	            @method('PUT')

                @if($isInvestigatorUpdate)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Case Reference</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $crime->case_number }} - {{ $crime->title }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ $crime->crimeType->name ?? 'N/A' }} / {{ $crime->offenseType->name ?? 'N/A' }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Investigation Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                @foreach(\App\Models\Crime::STATUSES as $status)
                                    <option value="{{ $status }}" {{ old('status', $crime->status) == $status ? 'selected' : '' }}>{{ \App\Models\Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="status_notes" class="block text-sm font-medium text-gray-700 mb-1">Workflow Remarks <span id="statusNotesRequired" class="hidden text-red-500">*</span></label>
                            <textarea id="status_notes" name="status_notes" rows="2"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('status_notes', $crime->status_notes) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Required when changing the workflow status.</p>
                            @error('status_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="investigation_findings" class="block text-sm font-medium text-gray-700 mb-1">Investigation Findings</label>
                            <div class="mb-2 flex flex-wrap gap-2">
                                <button type="button" data-format-finding="bold" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">Bold</button>
                                <button type="button" data-format-finding="bullet" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">Bullet</button>
                            </div>
                            <textarea id="investigation_findings" name="investigation_findings" rows="7" data-findings-source
                                placeholder="Record findings, actions taken, interviews, observations, and case notes..."
                                class="hidden">{{ old('investigation_findings', $crime->investigation_findings) }}</textarea>
	                            <div contenteditable="true" data-findings-editor data-placeholder="Record findings, actions taken, interviews, observations, and case notes..."
	                                class="min-h-44 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500"></div>
                            @error('investigation_findings') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2 rounded-lg border border-emerald-100 bg-emerald-50/40 p-5">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-emerald-600">Upload Files</h3>
                                    <p class="mt-1 text-xs text-gray-500">Upload evidence documents, photos, or case files.</p>
                                </div>
                                <select name="evidence_category" class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 sm:w-48">
	                                @foreach(\App\Models\Evidence::CATEGORIES as $value => $label)
	                                    <option value="{{ $value }}" {{ old('evidence_category', 'evidence') === $value ? 'selected' : '' }}>{{ $label }}</option>
	                                @endforeach
                                </select>
                            </div>
	                            <div class="grid gap-5 md:grid-cols-[260px_1fr]">
	                                <label for="evidence_files" data-upload-dropzone class="flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-emerald-300 bg-white px-4 py-6 text-center transition hover:border-emerald-500 hover:bg-emerald-50">
	                                    <svg class="h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
	                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	                                    </svg>
	                                    <span class="mt-4 text-sm font-medium text-emerald-600">Drag and drop files here</span>
	                                    <span class="my-1 text-xs font-semibold text-emerald-500">OR</span>
	                                    <span class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">Browse Files</span>
	                                    <input id="evidence_files" name="evidence_files[]" type="file" multiple class="sr-only" accept="image/*,.pdf,.doc,.docx">
	                                </label>
	                                <div>
	                                    <div class="mb-2 flex items-center justify-between">
	                                        <p class="text-sm font-bold text-gray-900">Evidence Files</p>
	                                        <button type="button" data-add-evidence class="hidden rounded-md border border-gray-200 bg-white px-2.5 py-1 text-sm font-semibold text-emerald-600 shadow-sm transition hover:bg-emerald-50">+</button>
	                                    </div>
	                                    <div data-existing-evidence class="space-y-2">
	                                        @foreach($crime->evidence as $item)
	                                            <div data-existing-evidence-row class="flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2">
	                                                @if(in_array($item->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
	                                                    <img src="{{ route('evidence.show', $item) }}" alt="{{ $item->file_name }}" class="h-9 w-9 rounded object-cover">
	                                                @else
	                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-red-500 text-[9px] font-bold uppercase text-white">{{ strtoupper(pathinfo($item->file_name ?? 'file', PATHINFO_EXTENSION) ?: 'FILE') }}</div>
	                                                @endif
	                                                <div class="min-w-0 flex-1">
	                                                    <p class="truncate text-xs font-medium text-gray-500">{{ $item->file_name ?? 'Evidence file' }}</p>
	                                                    <div class="mt-1 h-1 overflow-hidden rounded-full bg-gray-200">
	                                                        <div class="h-full w-full rounded-full bg-emerald-500"></div>
	                                                    </div>
	                                                </div>
	                                                <button type="button" data-remove-existing-evidence="{{ $item->id }}" class="shrink-0 text-xs font-medium text-gray-400 transition hover:text-red-600">Remove</button>
	                                            </div>
	                                        @endforeach
	                                    </div>
	                                    <div data-upload-preview class="mt-2 hidden space-y-2"></div>
	                                    <p data-upload-empty class="{{ $crime->evidence->count() > 0 ? 'hidden' : '' }} rounded-md border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">No files selected.</p>
	                                </div>
	                            </div>
		                            @error('evidence_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
	                            @error('evidence_files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
	                            @error('evidence_files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @else

            <!-- Location Picker -->
            <div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pin Incident Location</label>
                        <p class="mt-1 text-xs text-gray-500">Click the map or drag the marker to update the latitude and longitude.</p>
                    </div>
                    <button type="button" id="clearLocation" class="w-fit rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                        Clear Pin
                    </button>
                </div>
                <div class="mt-3 overflow-hidden rounded-lg border border-gray-300">
                    <div id="incidentLocationMap"></div>
                </div>
                <div id="pinLocationStatus" class="mt-3 hidden rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $crime->title) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Crime Type -->
                <div>
                    <label for="crime_type_id" class="block text-sm font-medium text-gray-700 mb-1">Crime Type <span class="text-red-500">*</span></label>
                    <select id="crime_type_id" name="crime_type_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        @foreach($crimeTypes as $type)
                            <option value="{{ $type->id }}" {{ old('crime_type_id', $crime->crime_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Offense -->
                <div>
                    <label for="offense_type_id" class="block text-sm font-medium text-gray-700 mb-1">Offense <span class="text-red-500">*</span></label>
                    <select id="offense_type_id" name="offense_type_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Select Offense</option>
                        @foreach($offenseTypes as $offenseType)
                            <option value="{{ $offenseType->id }}" data-crime-type-id="{{ $offenseType->crime_type_id }}" title="{{ $offenseType->name }}" {{ old('offense_type_id', $crime->offense_type_id) == $offenseType->id ? 'selected' : '' }}>{{ $offenseType->name }}</option>
                        @endforeach
                    </select>
                    <p id="selectedOffenseText" class="mt-1 hidden text-xs leading-5 text-gray-500"></p>
                    @error('offense_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Stage of Felony -->
                <div>
                    <label for="stage_of_felony" class="block text-sm font-medium text-gray-700 mb-1">Stage of Felony</label>
                    <select id="stage_of_felony" name="stage_of_felony"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Select Stage</option>
                        <option value="consummated" {{ old('stage_of_felony', $crime->stage_of_felony) === 'consummated' ? 'selected' : '' }}>Consummated</option>
                        <option value="frustrated" {{ old('stage_of_felony', $crime->stage_of_felony) === 'frustrated' ? 'selected' : '' }}>Frustrated</option>
                        <option value="attempted" {{ old('stage_of_felony', $crime->stage_of_felony) === 'attempted' ? 'selected' : '' }}>Attempted</option>
                    </select>
                    @error('stage_of_felony') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Barangay -->
                <div>
                    <label for="barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select id="barangay_id" name="barangay_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}" {{ old('barangay_id', $crime->barangay_id) == $barangay->id ? 'selected' : '' }}>{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        @foreach(\App\Models\Crime::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $crime->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Notes -->
                <div>
                    <label for="status_notes" class="block text-sm font-medium text-gray-700 mb-1">Workflow Remarks <span id="statusNotesRequired" class="hidden text-red-500">*</span></label>
                    <textarea id="status_notes" name="status_notes" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('status_notes', $crime->status_notes) }}</textarea>
                    <p id="statusNotesHelp" class="mt-1 text-xs text-gray-500">Required when changing the workflow status.</p>
                    @error('status_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Date Reported -->
                <div>
                    <label for="date_reported" class="block text-sm font-medium text-gray-700 mb-1">Date Reported <span class="text-red-500">*</span></label>
                    <input type="date" id="date_reported" name="date_reported" value="{{ old('date_reported', $crime->date_reported ? $crime->date_reported->format('Y-m-d') : '') }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <p class="mt-1 text-xs text-gray-500">Shown as year-month-day while editing.</p>
                    @error('date_reported') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Time Reported -->
                <div>
                    <label for="time_reported" class="block text-sm font-medium text-gray-700 mb-1">Time Reported</label>
                    <input type="time" id="time_reported" name="time_reported" value="{{ old('time_reported', $crime->time_reported ? $crime->time_reported->format('H:i') : '') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('time_reported') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Date Committed -->
                <div>
                    <label for="date_occurred" class="block text-sm font-medium text-gray-700 mb-1">Date Committed <span class="text-red-500">*</span></label>
                    <input type="date" id="date_occurred" name="date_occurred" value="{{ old('date_occurred', $crime->date_occurred->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <p class="mt-1 text-xs text-gray-500">Shown as year-month-day while editing.</p>
                    @error('date_occurred') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Time Committed -->
                <div>
                    <label for="time_occurred" class="block text-sm font-medium text-gray-700 mb-1">Time Committed</label>
                    <input type="time" id="time_occurred" name="time_occurred" value="{{ old('time_occurred', $crime->time_occurred ? $crime->time_occurred->format('H:i') : '') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('time_occurred') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address / Location <span class="text-red-500">*</span></label>
                    <input type="text" id="address" name="address" value="{{ old('address', $crime->address) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Coordinates -->
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $crime->latitude) }}" placeholder="6.4983"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="text" id="longitude" name="longitude" value="{{ old('longitude', $crime->longitude) }}" placeholder="124.8463"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Assigned Investigator -->
                @if(auth()->user()->isAdmin())
                <div>
                    <label for="reported_by" class="block text-sm font-medium text-gray-700 mb-1">Reporting Police Officer</label>
                    <select id="reported_by" name="reported_by"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Admin</option>
                        @foreach($policeOfficers as $policeOfficer)
                            <option value="{{ $policeOfficer->id }}" {{ old('reported_by', $crime->reported_by) == $policeOfficer->id ? 'selected' : '' }}>{{ $policeOfficer->name }}</option>
                        @endforeach
                    </select>
                    @error('reported_by') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label for="assigned_officer" class="block text-sm font-medium text-gray-700 mb-1">Assigned Investigator</label>
                    @if(auth()->user()->isInvestigator())
                        <input type="hidden" id="assigned_officer" name="assigned_officer" value="{{ $crime->assigned_officer }}">
                        <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-900">
                            {{ $crime->assignedOfficer->name ?? auth()->user()->name }}
                        </div>
                    @else
                        <select id="assigned_officer" name="assigned_officer"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Unassigned</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" {{ old('assigned_officer', $crime->assigned_officer) == $officer->id ? 'selected' : '' }}>{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('assigned_officer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Investigation Findings -->
                <div class="md:col-span-2">
                    <label for="investigation_findings" class="block text-sm font-medium text-gray-700 mb-1">Investigation Findings</label>
                    <div class="mb-2 flex flex-wrap gap-2">
                        <button type="button" data-format-finding="bold" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">Bold</button>
                        <button type="button" data-format-finding="bullet" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">Bullet</button>
                    </div>
                    <textarea id="investigation_findings" name="investigation_findings" rows="5" data-findings-source
                        class="hidden">{{ old('investigation_findings', $crime->investigation_findings) }}</textarea>
	                    <div contenteditable="true" data-findings-editor data-placeholder="Record findings, actions taken, interviews, observations, and case notes..."
	                        class="min-h-36 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500"></div>
                    @error('investigation_findings') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Evidence Upload -->
	                <div class="md:col-span-2 rounded-lg border border-emerald-100 bg-emerald-50/40 p-5">
	                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
	                        <div>
	                            <h3 class="text-xl font-bold text-emerald-600">Upload Files</h3>
	                            <p class="mt-1 text-xs text-gray-500">Upload evidence documents, photos, or case files.</p>
	                        </div>
	                        <select name="evidence_category" class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 sm:w-48">
	                        @foreach(\App\Models\Evidence::CATEGORIES as $value => $label)
	                            <option value="{{ $value }}" {{ old('evidence_category', 'evidence') === $value ? 'selected' : '' }}>{{ $label }}</option>
	                        @endforeach
	                        </select>
	                    </div>
	                    <div class="grid gap-5 md:grid-cols-[260px_1fr]">
	                        <label for="evidence_files" data-upload-dropzone class="flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-emerald-300 bg-white px-4 py-6 text-center transition hover:border-emerald-500 hover:bg-emerald-50">
	                            <svg class="h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
	                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
	                            </svg>
	                            <span class="mt-4 text-sm font-medium text-emerald-600">Drag and drop files here</span>
	                            <span class="my-1 text-xs font-semibold text-emerald-500">OR</span>
	                            <span class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">Browse Files</span>
	                            <input id="evidence_files" name="evidence_files[]" type="file" multiple class="sr-only" accept="image/*,.pdf,.doc,.docx">
	                        </label>
	                        <div>
	                            <div class="mb-2 flex items-center justify-between">
	                                <p class="text-sm font-bold text-gray-900">Evidence Files</p>
	                                <button type="button" data-add-evidence class="hidden rounded-md border border-gray-200 bg-white px-2.5 py-1 text-sm font-semibold text-emerald-600 shadow-sm transition hover:bg-emerald-50">+</button>
	                            </div>
	                            <div data-existing-evidence class="space-y-2">
	                                @foreach($crime->evidence as $item)
	                                    <div data-existing-evidence-row class="flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2">
	                                        @if(in_array($item->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
	                                            <img src="{{ route('evidence.show', $item) }}" alt="{{ $item->file_name }}" class="h-9 w-9 rounded object-cover">
	                                        @else
	                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-red-500 text-[9px] font-bold uppercase text-white">{{ strtoupper(pathinfo($item->file_name ?? 'file', PATHINFO_EXTENSION) ?: 'FILE') }}</div>
	                                        @endif
	                                        <div class="min-w-0 flex-1">
	                                            <p class="truncate text-xs font-medium text-gray-500">{{ $item->file_name ?? 'Evidence file' }}</p>
	                                            <div class="mt-1 h-1 overflow-hidden rounded-full bg-gray-200">
	                                                <div class="h-full w-full rounded-full bg-emerald-500"></div>
	                                            </div>
	                                        </div>
	                                        <button type="button" data-remove-existing-evidence="{{ $item->id }}" class="shrink-0 text-xs font-medium text-gray-400 transition hover:text-red-600">Remove</button>
	                                    </div>
	                                @endforeach
	                            </div>
	                            <div data-upload-preview class="mt-2 hidden space-y-2"></div>
	                            <p data-upload-empty class="{{ $crime->evidence->count() > 0 ? 'hidden' : '' }} rounded-md border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">No files selected.</p>
	                        </div>
	                    </div>
		                    @error('evidence_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
	                    @error('evidence_files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
	                    @error('evidence_files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
                @endif

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
	                <a href="{{ route('crimes.show', $crime) }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">Cancel</a>
		                <button type="submit" data-submit-label="{{ $isInvestigatorUpdate ? 'Save Investigation Details' : 'Update Incident' }}" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">{{ $isInvestigatorUpdate ? 'Save Investigation Details' : 'Update Incident' }}</button>
		            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
	document.addEventListener('DOMContentLoaded', function () {
	    const findingsEditors = Array.from(document.querySelectorAll('[data-findings-editor]'));
	    const formatButtons = Array.from(document.querySelectorAll('[data-format-finding]'));
	    let activeFindingsEditor = findingsEditors.find((editor) => editor.offsetParent !== null) || findingsEditors[0] || null;
	    const findingsPairs = findingsEditors.map((editor) => ({
	        editor,
	        source: editor.previousElementSibling?.matches('[data-findings-source]')
	            ? editor.previousElementSibling
	            : null,
	    }));
	
		    function markdownToEditorHtml(value) {
		        const lines = String(value || '').replace(/\r\n/g, '\n').split('\n');
		        const html = [];
		        let inList = false;
	
		        function formatInline(text) {
		            const escaped = text
		                .replace(/&/g, '&amp;')
		                .replace(/</g, '&lt;')
		                .replace(/>/g, '&gt;');
	
		            return escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
		        }
	
		        lines.forEach((line) => {
		            const trimmed = line.trim();
	
		            if (trimmed === '') {
		                if (inList) {
		                    html.push('</ul>');
		                    inList = false;
		                }
		                return;
		            }
	
		            const bullet = trimmed.match(/^[-*]\s+(.+)$/);
		            if (bullet) {
		                if (!inList) {
		                    html.push('<ul>');
		                    inList = true;
		                }
		                html.push(`<li>${formatInline(bullet[1])}</li>`);
		                return;
		            }
	
		            if (inList) {
		                html.push('</ul>');
		                inList = false;
		            }
		            html.push(`<p>${formatInline(trimmed)}</p>`);
		        });
	
		        if (inList) {
		            html.push('</ul>');
		        }
	
		        return html.join('');
		    }
	
		    function editorHtmlToMarkdown(editor) {
		        const lines = [];
	
		        function inlineMarkdown(node) {
		            if (node.nodeType === Node.TEXT_NODE) {
		                return node.textContent.replace(/\u00a0/g, ' ');
		            }
	
		            if (node.nodeType !== Node.ELEMENT_NODE) {
		                return '';
		            }
	
		            const tag = node.tagName.toLowerCase();
		            const text = Array.from(node.childNodes).map(inlineMarkdown).join('');
	
		            if (tag === 'strong' || tag === 'b') {
		                return `**${text}**`;
		            }
	
		            if (tag === 'br') {
		                return '\n';
		            }
	
		            return text;
		        }
	
		        function addLine(value, prefix = '') {
		            const trimmed = value.replace(/\n+/g, ' ').trim();
		            if (trimmed !== '') {
		                lines.push(prefix + trimmed);
		            }
		        }
	
		        function walkBlock(node) {
		            if (node.nodeType === Node.TEXT_NODE) {
		                addLine(node.textContent);
		                return;
		            }
	
		            if (node.nodeType !== Node.ELEMENT_NODE) {
		                return;
		            }
	
		            const tag = node.tagName.toLowerCase();
	
		            if (tag === 'ul' || tag === 'ol') {
		                Array.from(node.children).forEach((child) => {
		                    if (child.tagName?.toLowerCase() === 'li') {
		                        addLine(inlineMarkdown(child), '- ');
		                    }
		                });
		                return;
		            }
	
		            if (tag === 'li') {
		                addLine(inlineMarkdown(node), '- ');
		                return;
		            }
	
		            if (tag === 'p' || tag === 'div') {
		                const list = node.querySelector(':scope > ul, :scope > ol');
		                if (list) {
		                    Array.from(node.childNodes).forEach(walkBlock);
		                    return;
		                }
	
		                addLine(inlineMarkdown(node));
		                return;
		            }
	
		            addLine(inlineMarkdown(node));
		        }
	
		        Array.from(editor.childNodes).forEach(walkBlock);
	
		        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
		    }
	
	    function syncFindingsSource(editor) {
	        const pair = findingsPairs.find((item) => item.editor === editor);
	        if (pair?.source) {
		            pair.source.value = editorHtmlToMarkdown(editor);
	        }
	    }
	
	    findingsPairs.forEach(({ editor, source }) => {
		        if (source && !editor.innerText.trim()) {
		            editor.innerHTML = markdownToEditorHtml(source.value);
		        }
	
	        editor.addEventListener('input', () => syncFindingsSource(editor));
	    });
	
			    function setSubmitState(form, isSaving) {
			        const submitButton = form.querySelector('[type="submit"]');
			        if (!submitButton) return;

			        submitButton.disabled = isSaving;
			        submitButton.classList.toggle('cursor-wait', isSaving);
			        submitButton.classList.toggle('opacity-75', isSaving);
			        submitButton.textContent = isSaving ? 'Uploading...' : (submitButton.dataset.submitLabel || 'Update Incident');
			    }

						    function uploadPreviewRow(file, index) {
						        const row = document.createElement('div');
						        const isImage = file.type.startsWith('image/');
						        const extension = (file.name.split('.').pop() || 'file').slice(0, 4).toUpperCase();
						        const displayName = isImage ? 'Image evidence (100%)' : `${file.name} (100%)`;
						        row.dataset.previewIndex = String(index);
					        row.className = 'flex items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2';

					        if (isImage) {
					            row.innerHTML = `
					                <img data-image-preview alt="Selected evidence preview" class="h-9 w-9 rounded object-cover">
					                <div class="min-w-0 flex-1">
					                    <p class="truncate text-xs font-medium text-gray-500">${displayName}</p>
					                    <div class="mt-1 h-1 overflow-hidden rounded-full bg-gray-200">
					                        <div class="h-full w-full rounded-full bg-emerald-500"></div>
					                    </div>
					                </div>
					            `;
					            row.querySelector('[data-image-preview]').src = URL.createObjectURL(file);
					        } else {
					            row.innerHTML = `
					                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-red-500 text-[9px] font-bold uppercase text-white">${extension}</div>
					                <div class="min-w-0 flex-1">
					                    <p data-file-name class="truncate text-xs font-medium text-gray-500"></p>
					                    <div class="mt-1 h-1 overflow-hidden rounded-full bg-gray-200">
					                        <div class="h-full w-full rounded-full bg-emerald-500"></div>
					                    </div>
					                </div>
					            `;
					            row.querySelector('[data-file-name]').textContent = displayName;
					        }
					        row.insertAdjacentHTML('beforeend', `
						            <button type="button" data-remove-evidence="${index}" aria-label="Remove selected evidence" class="shrink-0 text-xs font-medium text-gray-400 transition hover:text-red-600">
						                Cancel
						            </button>
						        `);

					        return row;
				    }

					    function syncEvidenceInput(input, files) {
				        const transfer = new DataTransfer();
				        files.forEach((file) => transfer.items.add(file));
				        input.files = transfer.files;
				    }

					    function renderEvidencePreview(input) {
					        const files = input._selectedEvidenceFiles || [];
						        const wrapper = input.closest('.md\\:col-span-2');
						        const preview = wrapper?.querySelector('[data-upload-preview]');
						        const empty = wrapper?.querySelector('[data-upload-empty]');
						        const addButton = wrapper?.querySelector('[data-add-evidence]');
						        if (!preview) return;

				        preview.querySelectorAll('[data-image-preview]').forEach((image) => {
				            if (image.src) {
				                URL.revokeObjectURL(image.src);
				            }
				        });

				        preview.innerHTML = '';

					        if (files.length === 0) {
					            preview.classList.add('hidden');
					            updateEvidenceEmptyState(wrapper);
					            return;
					        }

					        files.forEach((file, index) => preview.appendChild(uploadPreviewRow(file, index)));
					        preview.classList.remove('hidden');
					        empty?.classList.add('hidden');
					        addButton?.classList.remove('hidden');
					    }

					    function updateEvidenceEmptyState(wrapper) {
					        const hasExisting = Boolean(wrapper.querySelector('[data-existing-evidence-row]:not(.hidden)'));
					        const hasNew = Boolean(wrapper.querySelector('[data-upload-preview]:not(.hidden) > *'));
					        const empty = wrapper.querySelector('[data-upload-empty]');
					        const addButton = wrapper.querySelector('[data-add-evidence]');

					        empty?.classList.toggle('hidden', hasExisting || hasNew);
					        addButton?.classList.toggle('hidden', !(hasExisting || hasNew));
					    }

				    document.querySelectorAll('form').forEach((form) => {
				        form.addEventListener('submit', () => {
				            findingsEditors.forEach(syncFindingsSource);
				            setSubmitState(form, true);
				        });
				    });

				    document.querySelectorAll('input[type="file"][name="evidence_files[]"]').forEach((input) => {
				        input._selectedEvidenceFiles = [];

				        function addFiles(files) {
				            input._selectedEvidenceFiles = input._selectedEvidenceFiles.concat(Array.from(files || []));
				            syncEvidenceInput(input, input._selectedEvidenceFiles);
				            renderEvidencePreview(input);
				        }

				        input.addEventListener('change', () => {
				            const selectedFiles = Array.from(input.files || []);
				            addFiles(selectedFiles);
				        });

				        const wrapper = input.closest('.md\\:col-span-2');
				        const dropzone = wrapper?.querySelector('[data-upload-dropzone]');

				        wrapper?.querySelector('[data-upload-preview]')?.addEventListener('click', (event) => {
				            const removeButton = event.target.closest('[data-remove-evidence]');

				            if (removeButton) {
				                const index = Number(removeButton.dataset.removeEvidence);
				                input._selectedEvidenceFiles.splice(index, 1);
				                syncEvidenceInput(input, input._selectedEvidenceFiles);
				                renderEvidencePreview(input);
				                updateEvidenceEmptyState(wrapper);
				            }
				        });

				        wrapper?.querySelector('[data-add-evidence]')?.addEventListener('click', () => input.click());

				        wrapper?.querySelector('[data-existing-evidence]')?.addEventListener('click', (event) => {
				            const removeButton = event.target.closest('[data-remove-existing-evidence]');
				            if (!removeButton) return;

				            const hiddenInput = document.createElement('input');
				            hiddenInput.type = 'hidden';
				            hiddenInput.name = 'remove_evidence_ids[]';
				            hiddenInput.value = removeButton.dataset.removeExistingEvidence;
				            wrapper.closest('form')?.appendChild(hiddenInput);

				            removeButton.closest('[data-existing-evidence-row]')?.classList.add('hidden');
				            updateEvidenceEmptyState(wrapper);
				        });

				        if (wrapper) {
				            updateEvidenceEmptyState(wrapper);
				        }

				        dropzone?.addEventListener('dragover', (event) => {
				            event.preventDefault();
				            dropzone.classList.add('border-emerald-500', 'bg-emerald-50');
				        });

				        dropzone?.addEventListener('dragleave', () => {
				            dropzone.classList.remove('border-emerald-500', 'bg-emerald-50');
				        });

				        dropzone?.addEventListener('drop', (event) => {
				            event.preventDefault();
				            dropzone.classList.remove('border-emerald-500', 'bg-emerald-50');
				            addFiles(event.dataTransfer?.files);
				        });
				    });

    findingsEditors.forEach((editor) => {
        editor.addEventListener('focus', () => {
            activeFindingsEditor = editor;
        });
    });

		    function formatFindingText(format) {
		        const editor = activeFindingsEditor;
		        if (!editor) return;
	
	        editor.focus();
	        const selection = window.getSelection();
	        const selected = selection && editor.contains(selection.anchorNode)
	            ? selection.toString()
	            : '';
	
			        if (format === 'bold') {
			            document.execCommand('bold', false, null);
			            syncFindingsSource(editor);
			
			            return;
			        }
			
			        if (format === 'bullet') {
			            const selectedLines = selected
			                .split('\n')
			                .map((line) => line.trim())
			                .filter(Boolean);
			            const listItems = selectedLines.length > 0
			                ? selectedLines
			                : [''];
			            const listHtml = `<ul>${listItems.map((line) => `<li>${line || '<br>'}</li>`).join('')}</ul>`;
	
			            document.execCommand('insertHTML', false, listHtml);
			            syncFindingsSource(editor);
			            setTimeout(() => syncFindingsSource(editor), 0);
			        }
		    }

    formatButtons.forEach((button) => {
        button.addEventListener('click', () => formatFindingText(button.dataset.formatFinding));
    });

    const crimeTypeSelect = document.getElementById('crime_type_id');
    const offenseSelect = document.getElementById('offense_type_id');
    const selectedOffenseText = document.getElementById('selectedOffenseText');
    const offenseOptions = offenseSelect ? Array.from(offenseSelect.options) : [];

    function updateSelectedOffenseText() {
        if (!offenseSelect || !selectedOffenseText) return;

        const text = offenseSelect.selectedOptions[0]?.textContent?.trim() || '';
        selectedOffenseText.textContent = text;
        selectedOffenseText.classList.toggle('hidden', text === '' || offenseSelect.value === '');
    }

    function syncOffenseToCrimeType(shouldAutoSelect = true) {
        if (!crimeTypeSelect || !offenseSelect) return;

        const selectedCrimeType = crimeTypeSelect.value;
        const currentOffense = offenseSelect.value;
        let firstMatchingValue = '';
        let currentStillVisible = false;

        offenseOptions.forEach((option) => {
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
            offenseSelect.value = shouldAutoSelect ? firstMatchingValue : '';
        }

        updateSelectedOffenseText();
    }

	    crimeTypeSelect?.addEventListener('change', () => syncOffenseToCrimeType(true));
	    offenseSelect?.addEventListener('change', updateSelectedOffenseText);
	    syncOffenseToCrimeType(!offenseSelect?.value);

	    const statusSelect = document.getElementById('status');
	    const statusNotes = document.getElementById('status_notes');
	    const statusNotesRequired = document.getElementById('statusNotesRequired');

	    function updateStatusNotesRequirement() {
	        if (!statusSelect || !statusNotes || !statusNotesRequired) return;

	        const currentStatus = @json($crime->status);
	        const required = statusSelect.value !== currentStatus;
	        statusNotes.required = required;
	        statusNotesRequired.classList.toggle('hidden', !required);
	    }

	    statusSelect?.addEventListener('change', updateStatusNotesRequirement);
	    updateStatusNotesRequirement();

	    const mapTarget = document.getElementById('incidentLocationMap');
	    if (!mapTarget) return;

    if (typeof L === 'undefined') {
        mapTarget.innerHTML = `
            <div class="flex h-full min-h-[360px] items-center justify-center bg-slate-50 p-6 text-center">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Map library failed to load.</p>
                    <p class="mt-1 text-xs text-slate-500">You can still type the latitude and longitude manually, then save the incident.</p>
                </div>
            </div>
        `;

        return;
    }

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const barangaySelect = document.getElementById('barangay_id');
	    const clearButton = document.getElementById('clearLocation');
    const pinStatus = document.getElementById('pinLocationStatus');
    const detectBarangayUrl = '{{ route("map.detect-barangay") }}';
    const barangays = {!! $barangays->map(fn ($barangay) => [
        'id' => $barangay->id,
        'name' => $barangay->name,
        'lat' => $barangay->latitude ? (float) $barangay->latitude : null,
        'lng' => $barangay->longitude ? (float) $barangay->longitude : null,
    ])->values()->toJson() !!};
    const barangayLookup = Object.fromEntries(barangays.map((barangay) => [String(barangay.id), barangay]));
	    const barangayNameLookup = barangays.map((barangay) => ({
	        id: String(barangay.id),
	        name: barangay.name,
	        normalized: normalizeBarangayName(barangay.name)
	    }));
    const koronadalBounds = L.latLngBounds([6.351757, 124.788841], [6.569806, 125.000435]);
    const defaultCenter = [6.504525, 124.891346];

    const map = L.map('incidentLocationMap', {
        center: defaultCenter,
        zoom: 13,
        minZoom: 11,
        maxBounds: koronadalBounds,
        maxBoundsViscosity: 0.9,
        attributionControl: false
    }).setView(defaultCenter, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        bounds: koronadalBounds,
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    let marker = null;
    let detectionRequestId = 0;

    function hasCoordinates() {
        return latInput.value !== '' && lngInput.value !== '' && !Number.isNaN(Number(latInput.value)) && !Number.isNaN(Number(lngInput.value));
    }

	    function showPinStatus(message, tone = 'info') {
	        if (!pinStatus) return;

        const classes = tone === 'error'
            ? 'mt-3 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800'
            : 'mt-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800';

        pinStatus.className = classes;
	        pinStatus.textContent = message;
	        pinStatus.classList.remove('hidden');
	    }

	    function normalizeBarangayName(value) {
	        return String(value || '')
	            .toLowerCase()
	            .replace(/\([^)]*\)/g, '')
	            .replace(/ÃƒÂ±|Ãƒâ€˜|Ã±|Ã‘/g, 'n')
	            .replace(/\bbarangay\b|\bbrgy\.?\b/g, '')
	            .replace(/\bsto\.?\b/g, 'santo')
	            .replace(/\bsta\.?\b/g, 'santa')
	            .replace(/\bzone\s*iv\b/g, 'zone4')
	            .replace(/\bzone\s*iii\b/g, 'zone3')
	            .replace(/\bzone\s*ii\b/g, 'zone2')
	            .replace(/\bzone\s*i\b/g, 'zone1')
	            .replace(/\bzone\s*one\b/g, 'zone1')
	            .replace(/\bzone\s*two\b/g, 'zone2')
	            .replace(/\bzone\s*three\b/g, 'zone3')
	            .replace(/\bzone\s*four\b/g, 'zone4')
	            .replace(/[^a-z0-9]+/g, '');
	    }

	    function isGenericBarangayAddress(value) {
	        const normalizedValue = normalizeBarangayName(value);

	        if (!normalizedValue) {
	            return true;
	        }

	        return barangayNameLookup.some((barangay) =>
	            barangay.normalized && (
	                normalizedValue === `${barangay.normalized}koronadalcity`
	                || normalizedValue === `${barangay.normalized}cityofkoronadal`
	                || normalizedValue === barangay.normalized
	            )
	        );
	    }

    function detectPinnedBarangay(lat, lng) {
        const requestId = ++detectionRequestId;
        showPinStatus('Detecting barangay...');

        fetch(`${detectBarangayUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.ok ? response.json() : Promise.reject())
            .then((data) => {
                if (!data || requestId !== detectionRequestId) return;

	                if (data.success && data.id) {
	                    const match = barangayNameLookup.find((barangay) => barangay.id === String(data.id));
	
	                    if (match) {
	                        barangaySelect.value = match.id;
	                        const addressInput = document.getElementById('address');
	                        if (addressInput && (!addressInput.value.trim() || isGenericBarangayAddress(addressInput.value))) {
	                            addressInput.value = `${match.name}, Koronadal City`;
	                        }
	                        const sourceLabel = data.method === 'nearest_distance' ? 'Nearest mapped area' : 'Detected boundary area';
	                        showPinStatus(`${sourceLabel}: ${match.name}.`);
	                        return;
                    }
                }

                showPinStatus('Barangay was not detected; please select it manually.', 'error');
            })
            .catch(() => {
                if (requestId !== detectionRequestId) return;
                showPinStatus('Barangay was not detected; please select it manually.', 'error');
            });
    }

    function setMarker(lat, lng, shouldPan = true, shouldDetect = true) {
        const point = L.latLng(Number(lat), Number(lng));

        if (!koronadalBounds.contains(point)) {
            showPinStatus('Selected location is outside Koronadal City. Please place the pin inside the mapped area.', 'error');
            return;
        }

        latInput.value = point.lat.toFixed(7);
        lngInput.value = point.lng.toFixed(7);

        if (!marker) {
            marker = L.marker(point, { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                const moved = marker.getLatLng();
                setMarker(moved.lat, moved.lng, false);
            });
        } else {
            marker.setLatLng(point);
        }

        if (shouldPan) {
            map.setView(point, Math.max(map.getZoom(), 15));
        }

        if (shouldDetect) {
            detectPinnedBarangay(point.lat, point.lng);
        }
    }

    map.on('click', function (event) {
        setMarker(event.latlng.lat, event.latlng.lng);
    });

    barangaySelect?.addEventListener('change', function () {
        const barangay = barangayLookup[String(this.value)];
        if (barangay?.lat && barangay?.lng) {
            map.setView([barangay.lat, barangay.lng], 15);
        }
    });

    clearButton?.addEventListener('click', function () {
        latInput.value = '';
        lngInput.value = '';
        detectionRequestId++;
        pinStatus?.classList.add('hidden');
        if (marker) {
            marker.remove();
            marker = null;
        }
        map.fitBounds(koronadalBounds);
    });


    if (hasCoordinates()) {
        setMarker(latInput.value, lngInput.value, true, false);
    } else {
        const selectedBarangay = barangayLookup[String(barangaySelect?.value || '')];
        if (selectedBarangay?.lat && selectedBarangay?.lng) {
            map.setView([selectedBarangay.lat, selectedBarangay.lng], 15);
        } else {
            map.fitBounds(koronadalBounds);
        }
    }

    setTimeout(() => map.invalidateSize(), 150);
});
</script>
@endpush

