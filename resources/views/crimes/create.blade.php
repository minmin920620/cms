@extends('layouts.app')

@section('title', 'Report Incident')
@section('page_title', 'Report New Incident')

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
        <form method="POST" action="{{ route('crimes.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Location Picker -->
            <div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pin Incident Location</label>
                        <p class="mt-1 text-xs text-gray-500">Click the map or drag the marker to fill the latitude, longitude, barangay, and timestamp.</p>
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
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Crime Type -->
                <div>
                    <label for="crime_type_id" class="block text-sm font-medium text-gray-700 mb-1">Crime Type <span class="text-red-500">*</span></label>
                    <select id="crime_type_id" name="crime_type_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Select Crime Type</option>
                        @foreach($crimeTypes as $type)
                            <option value="{{ $type->id }}" {{ old('crime_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('crime_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Offense -->
                <div>
                    <label for="offense_type_id" class="block text-sm font-medium text-gray-700 mb-1">Offense <span class="text-red-500">*</span></label>
                    <select id="offense_type_id" name="offense_type_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Select Offense</option>
                        @foreach($offenseTypes as $offenseType)
                            <option value="{{ $offenseType->id }}" data-crime-type-id="{{ $offenseType->crime_type_id }}" title="{{ $offenseType->name }}" {{ old('offense_type_id') == $offenseType->id ? 'selected' : '' }}>{{ $offenseType->name }}</option>
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
                        <option value="consummated" {{ old('stage_of_felony') === 'consummated' ? 'selected' : '' }}>Consummated</option>
                        <option value="frustrated" {{ old('stage_of_felony') === 'frustrated' ? 'selected' : '' }}>Frustrated</option>
                        <option value="attempted" {{ old('stage_of_felony') === 'attempted' ? 'selected' : '' }}>Attempted</option>
                    </select>
                    @error('stage_of_felony') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Barangay -->
                <div>
                    <label for="barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select id="barangay_id" name="barangay_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Select Barangay</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}" {{ old('barangay_id') == $barangay->id ? 'selected' : '' }}>{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                    @error('barangay_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Date Reported -->
                <div>
                    <label for="date_reported" class="block text-sm font-medium text-gray-700 mb-1">Date Reported <span class="text-red-500">*</span></label>
                    <input type="date" id="date_reported" name="date_reported" value="{{ old('date_reported', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <p class="mt-1 text-xs text-gray-500">Shown as year-month-day while editing.</p>
                    @error('date_reported') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Time Reported -->
                <div>
                    <label for="time_reported" class="block text-sm font-medium text-gray-700 mb-1">Time Reported</label>
                    <input type="time" id="time_reported" name="time_reported" value="{{ old('time_reported') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('time_reported') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Date Committed -->
                <div>
                    <label for="date_occurred" class="block text-sm font-medium text-gray-700 mb-1">Date Committed <span class="text-red-500">*</span></label>
                    <input type="date" id="date_occurred" name="date_occurred" value="{{ old('date_occurred', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <p class="mt-1 text-xs text-gray-500">Shown as year-month-day while editing.</p>
                    @error('date_occurred') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Time Committed -->
                <div>
                    <label for="time_occurred" class="block text-sm font-medium text-gray-700 mb-1">Time Committed</label>
                    <input type="time" id="time_occurred" name="time_occurred" value="{{ old('time_occurred') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('time_occurred') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address / Location <span class="text-red-500">*</span></label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" placeholder="Street, barangay, city..." required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Coordinates -->
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}" placeholder="6.4983"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}" placeholder="124.8463"
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
                            <option value="{{ $policeOfficer->id }}" {{ old('reported_by') == $policeOfficer->id ? 'selected' : '' }}>{{ $policeOfficer->name }}</option>
                        @endforeach
                    </select>
                    @error('reported_by') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label for="assigned_officer" class="block text-sm font-medium text-gray-700 mb-1">Assigned Investigator</label>
                    @if(auth()->user()->isInvestigator())
                        <input type="hidden" id="assigned_officer" name="assigned_officer" value="{{ auth()->id() }}">
                        <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-900">
                            {{ auth()->user()->name }}
                        </div>
                    @else
                        <select id="assigned_officer" name="assigned_officer"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Unassigned</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" {{ old('assigned_officer') == $officer->id ? 'selected' : '' }}>{{ $officer->name }}</option>
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
	                    <textarea id="investigation_findings" name="investigation_findings" rows="4" data-findings-source
	                        placeholder="Initial findings, investigator notes, or observations..."
	                        class="hidden">{{ old('investigation_findings') }}</textarea>
	                    <div contenteditable="true" data-findings-editor data-placeholder="Initial findings, investigator notes, or observations..."
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
                            <div data-upload-preview class="hidden space-y-2"></div>
                            <p data-upload-empty class="rounded-md border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">No files selected.</p>
                        </div>
                    </div>
                    @error('evidence_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('evidence_files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('evidence_files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('crimes.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Report Incident</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const crimeTypeSelect = document.getElementById('crime_type_id');
	    const offenseSelect = document.getElementById('offense_type_id');
	    const selectedOffenseText = document.getElementById('selectedOffenseText');
	    const offenseOptions = offenseSelect ? Array.from(offenseSelect.options) : [];
	    const findingsEditor = document.querySelector('[data-findings-editor]');
	    const findingsSource = document.querySelector('[data-findings-source]');
	    const formatButtons = Array.from(document.querySelectorAll('[data-format-finding]'));

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

	    function syncFindingsSource() {
	        if (findingsEditor && findingsSource) {
	            findingsSource.value = editorHtmlToMarkdown(findingsEditor);
	        }
	    }

	    if (findingsEditor && findingsSource) {
	        findingsEditor.innerHTML = markdownToEditorHtml(findingsSource.value);
	        findingsEditor.addEventListener('input', syncFindingsSource);
	    }

	    formatButtons.forEach((button) => {
	        button.addEventListener('click', () => {
	            if (!findingsEditor) return;

	            findingsEditor.focus();

	            if (button.dataset.formatFinding === 'bold') {
	                document.execCommand('bold', false, null);
	            }

	            if (button.dataset.formatFinding === 'bullet') {
	                document.execCommand('insertUnorderedList', false, null);
	            }

	            syncFindingsSource();
	        });
	    });

	    document.querySelector('form')?.addEventListener('submit', syncFindingsSource);
	
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
	            empty?.classList.remove('hidden');
	            addButton?.classList.add('hidden');
	            return;
	        }

	        files.forEach((file, index) => preview.appendChild(uploadPreviewRow(file, index)));
	        preview.classList.remove('hidden');
	        empty?.classList.add('hidden');
	        addButton?.classList.remove('hidden');
	    }

	    document.querySelectorAll('input[type="file"][name="evidence_files[]"]').forEach((input) => {
	        input._selectedEvidenceFiles = [];

	        function addFiles(files) {
	            input._selectedEvidenceFiles = input._selectedEvidenceFiles.concat(Array.from(files || []));
	            syncEvidenceInput(input, input._selectedEvidenceFiles);
	            renderEvidencePreview(input);
	        }

	        input.addEventListener('change', () => addFiles(input.files));

	        const wrapper = input.closest('.md\\:col-span-2');
	        const dropzone = wrapper?.querySelector('[data-upload-dropzone]');

	        wrapper?.querySelector('[data-upload-preview]')?.addEventListener('click', (event) => {
	            const removeButton = event.target.closest('[data-remove-evidence]');
	            if (!removeButton) return;

	            const index = Number(removeButton.dataset.removeEvidence);
	            input._selectedEvidenceFiles.splice(index, 1);
	            syncEvidenceInput(input, input._selectedEvidenceFiles);
	            renderEvidencePreview(input);
	        });

	        wrapper?.querySelector('[data-add-evidence]')?.addEventListener('click', () => input.click());

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

	    const mapTarget = document.getElementById('incidentLocationMap');
    if (!mapTarget) return;

    if (typeof L === 'undefined') {
        mapTarget.innerHTML = `
            <div class="flex h-full min-h-[360px] items-center justify-center bg-slate-50 p-6 text-center">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Map library failed to load.</p>
                    <p class="mt-1 text-xs text-slate-500">You can still type the latitude and longitude manually, then submit the form.</p>
                </div>
            </div>
        `;

        return;
    }

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const barangaySelect = document.getElementById('barangay_id');
    const dateReportedInput = document.getElementById('date_reported');
    const timeReportedInput = document.getElementById('time_reported');
    const dateOccurredInput = document.getElementById('date_occurred');
    const timeOccurredInput = document.getElementById('time_occurred');
    const addressInput = document.getElementById('address');
    const clearButton = document.getElementById('clearLocation');
    const pinStatus = document.getElementById('pinLocationStatus');
    const detectBarangayUrl = '{{ route("map.detect-barangay") }}';
    const reverseGeocodeUrl = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode';
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

    function padTimePart(value) {
        return String(value).padStart(2, '0');
    }

    function localTimestamp() {
        const now = new Date();

        return {
            date: `${now.getFullYear()}-${padTimePart(now.getMonth() + 1)}-${padTimePart(now.getDate())}`,
            time: `${padTimePart(now.getHours())}:${padTimePart(now.getMinutes())}`,
            label: now.toLocaleString([], {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })
        };
    }

    function applyPinTimestamp() {
        const timestamp = localTimestamp();

        dateReportedInput.value = timestamp.date;
        timeReportedInput.value = timestamp.time;

        if (!dateOccurredInput.value) {
            dateOccurredInput.value = timestamp.date;
        }

        if (!timeOccurredInput.value) {
            timeOccurredInput.value = timestamp.time;
        }

        return timestamp;
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
            .replace(/Ã±|Ã‘|ñ|Ñ/g, 'n')
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

	    function findBarangayInText(text) {
	        const normalizedText = normalizeBarangayName(text);
	
	        return barangayNameLookup.find((barangay) =>
	            barangay.normalized && normalizedText.includes(barangay.normalized)
	        ) || null;
	    }

	    function isGenericBarangayAddress(value) {
	        const normalizedValue = normalizeBarangayName(value);

	        if (!normalizedValue) {
	            return true;
	        }

	        return barangayNameLookup.some((barangay) =>
	            normalizedValue === `${barangay.normalized}koronadalcity`
	            || normalizedValue === `${barangay.normalized}cityofkoronadal`
	            || normalizedValue === barangay.normalized
	        );
	    }
	
	    function selectDetectedBarangay(match, timestamp, sourceLabel) {
	        barangaySelect.value = match.id;
	
	        if (addressInput && (!addressInput.value.trim() || isGenericBarangayAddress(addressInput.value))) {
	            addressInput.value = `${match.name}, Koronadal City`;
	        }

        showPinStatus(`${sourceLabel}: ${match.name}. Timestamp: ${timestamp.label}`);
    }

    function reverseGeocodeBarangay(lat, lng) {
        const params = new URLSearchParams({
            location: `${lng},${lat}`,
            f: 'json',
            langCode: 'en'
        });

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 3500);

        return fetch(`${reverseGeocodeUrl}?${params.toString()}`, {
            signal: controller.signal,
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.ok ? response.json() : Promise.reject())
            .then((data) => {
                const address = data.address || {};
                const text = [
                    address.Neighborhood,
                    address.District,
                    address.City,
                    address.Subregion,
                    address.Region,
                    address.LongLabel,
                    address.Match_addr
                ].filter(Boolean).join(' ');

                return findBarangayInText(text);
            })
            .finally(() => clearTimeout(timeout));
    }

    function detectPinnedBarangay(lat, lng, timestamp) {
        const requestId = ++detectionRequestId;

        showPinStatus(`Detecting barangay... Timestamp: ${timestamp.label}`);

        fetch(`${detectBarangayUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.ok ? response.json() : Promise.reject())
            .then((data) => {
                if (!data || requestId !== detectionRequestId) return;

                if (data.success && data.id) {
                    const match = barangayNameLookup.find((barangay) => barangay.id === String(data.id));

                    if (match) {
                        const sourceLabel = data.method === 'nearest_distance' ? 'Nearest mapped area' : 'Detected boundary area';
                        selectDetectedBarangay(match, timestamp, sourceLabel);
                        return;
                    }
                }

                showPinStatus(`Pin timestamp: ${timestamp.label}. Barangay was not detected; please select it manually.`, 'error');
            })
            .catch(() => {
                if (requestId !== detectionRequestId) return;
                showPinStatus(`Pin timestamp: ${timestamp.label}. Barangay was not detected; please select it manually.`, 'error');
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
            const timestamp = applyPinTimestamp();
            detectPinnedBarangay(point.lat, point.lng, timestamp);
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

