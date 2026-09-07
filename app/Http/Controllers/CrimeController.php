<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\Evidence;
use App\Models\OffenseType;
use App\Models\SystemNotification;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrimeController extends Controller
{
    private const KORONADAL_BOUNDS = [
        'south' => 6.351757,
        'north' => 6.569806,
        'west' => 124.788841,
        'east' => 125.000435,
    ];

    public function index(Request $request): View
    {
        $query = Crime::with(['crimeType', 'offenseType', 'barangay', 'reporter', 'assignedOfficer']);

        // Apply filters
        if ($request->filled('crime_type_id')) {
            $query->byType($request->crime_type_id);
        }
        if ($request->filled('barangay_id')) {
            $query->byBarangay($request->barangay_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date_occurred', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_occurred', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('case_number', 'like', "%{$search}%")
                  ->orWhere('stage_of_felony', 'like', "%{$search}%")
                  ->orWhere('investigation_findings', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('offenseType', fn ($offenseQuery) => $offenseQuery->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_officer')
                ->whereIn('status', Crime::OPEN_STATUSES);
        }
        if ($request->boolean('missing_coordinates')) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        // Role-based filtering
        $user = Auth::user();
        if ($user->isPoliceOfficer()) {
            $query->where(function ($q) use ($user) {
                $q->where('reported_by', $user->id)
                  ->orWhere('assigned_officer', $user->id);
            });
        } elseif ($user->isInvestigator()) {
            $query->where('assigned_officer', $user->id);
        }

        $crimes = $query->orderBy('created_at', 'desc')->paginate(15);
        $crimeTypes = CrimeType::crimeTypes((int) $request->crime_type_id);
        $barangays = Barangay::where('city', 'Koronadal City')->get();
        $statuses = Crime::STATUSES;

        return view('crimes.index', compact('crimes', 'crimeTypes', 'barangays', 'statuses'));
    }

    public function create(): View
    {
        $crimeTypes = CrimeType::crimeTypes();
        $offenseTypes = OffenseType::offenseChoices();
        $barangays = Barangay::where('city', 'Koronadal City')->get();
        $policeOfficers = User::where('role', 'police_officer')
            ->where('is_active', true)
            ->get();
        $officers = User::where('role', 'investigator')
            ->where('is_active', true)
            ->get();

        return view('crimes.create', compact('crimeTypes', 'offenseTypes', 'barangays', 'policeOfficers', 'officers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCrimePayload($request, [
            'title' => 'required|string|max:255',
            'crime_type_id' => [
                'required',
                Rule::exists('crime_types', 'id')->where('is_active', true),
            ],
            'barangay_id' => 'required|exists:barangays,id',
            'date_reported' => 'required|date|after_or_equal:date_occurred',
            'time_reported' => 'nullable|date_format:H:i',
            'date_occurred' => 'required|date',
            'time_occurred' => 'nullable|date_format:H:i',
            'stage_of_felony' => 'nullable|in:consummated,frustrated,attempted',
            'offense_type_id' => [
                'required',
                Rule::exists('offense_types', 'id')->where('is_active', true),
            ],
            'investigation_findings' => 'nullable|string',
            'latitude' => 'nullable|numeric|min:' . self::KORONADAL_BOUNDS['south'] . '|max:' . self::KORONADAL_BOUNDS['north'],
            'longitude' => 'nullable|numeric|min:' . self::KORONADAL_BOUNDS['west'] . '|max:' . self::KORONADAL_BOUNDS['east'],
            'address' => 'required|string|max:500',
            'reported_by' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'police_officer')
                    ->where('is_active', true)),
            ],
            'assigned_officer' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'investigator')
                    ->where('is_active', true)),
            ],
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'evidence_category' => ['nullable', Rule::in(array_keys(Evidence::CATEGORIES))],
        ]);

        $validated['reported_by'] = Auth::user()->isAdmin()
            ? ($validated['reported_by'] ?? Auth::id())
            : Auth::id();
        if (Auth::user()->isInvestigator()) {
            $validated['assigned_officer'] = Auth::id();
        }
        $validated['status'] = Crime::STATUS_PENDING;
        $validated['date_reported'] ??= now()->toDateString();
        $this->syncBarangayFromCoordinates($validated);
        $validated['findings_recorded_at'] = filled($validated['investigation_findings'] ?? null) ? now() : null;

        $maxAttempts = 3;
        $attempt = 0;

        do {
            try {
                $validated['case_number'] = Crime::generateCaseNumber();
                $crime = Crime::create($validated);
                break;
            } catch (UniqueConstraintViolationException $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                // Retry with a fresh case number
                usleep(100000); // 100ms delay to allow concurrent request to finish
            }
        } while ($attempt < $maxAttempts);

        // Handle evidence upload
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                $disk = config('filesystems.evidence_disk', 'local');
                $path = $file->store('evidence/' . $crime->id, $disk);
                $evidence = $crime->evidence()->create([
                    'file_path' => $path,
                    'storage_disk' => $disk,
                    'file_type' => $file->getClientMimeType(),
                    'file_name' => $file->getClientOriginalName(),
                    'category' => $validated['evidence_category'] ?? 'evidence',
                    'uploaded_by' => Auth::id(),
                ]);

                Audit::log('evidence.uploaded', $evidence, [], $evidence->only([
                    'id', 'crime_id', 'file_path', 'storage_disk', 'file_name', 'file_type', 'uploaded_by',
                    'category',
                ]));
            }
        }

        Audit::log('crime.created', $crime, [], $crime->only([
            'id', 'case_number', 'title', 'crime_type_id', 'barangay_id', 'status', 'reported_by', 'assigned_officer',
            'investigation_findings', 'findings_recorded_at',
        ]));

        $this->recordStatusHistory($crime, null, $crime->status, 'Crime incident was reported and added to the crime mapping records.');
        if ($crime->assigned_officer) {
            $this->recordAssignmentHistory($crime, null, $crime->assigned_officer, 'Case assigned during incident creation.');
            $this->notifyAssignedInvestigator($crime);
        }

        return redirect()->route('crimes.index')
            ->with('success', 'Crime incident reported successfully.');
    }

    public function show(Crime $crime): View
    {
        $this->authorizeView($crime);

        $crime->load([
            'crimeType',
            'offenseType',
            'barangay',
            'reporter',
            'assignedOfficer',
            'evidence.uploadedBy',
            'statusHistories.changedBy',
            'assignmentHistories.oldUser',
            'assignmentHistories.newUser',
            'assignmentHistories.changedBy',
            'approvalRequester',
            'approver',
        ]);
        return view('crimes.show', compact('crime'));
    }

    public function print(Crime $crime): View
    {
        $this->authorizeView($crime);

        $crime->load([
            'crimeType',
            'offenseType',
            'barangay',
            'reporter',
            'assignedOfficer',
            'evidence.uploadedBy',
            'statusHistories.changedBy',
            'assignmentHistories.oldUser',
            'assignmentHistories.newUser',
            'assignmentHistories.changedBy',
        ]);

        return view('crimes.print', compact('crime'));
    }

    public function edit(Crime $crime): View
    {
        $this->authorizeEdit($crime);

        $crime->load('evidence.uploadedBy');
        $crimeTypes = CrimeType::crimeTypes($crime->crime_type_id);
        $offenseTypes = OffenseType::offenseChoices($crime->offense_type_id);
        $barangays = Barangay::where('city', 'Koronadal City')->get();
        $policeOfficers = User::where('role', 'police_officer')
            ->where('is_active', true)
            ->get();
        $officers = User::where('role', 'investigator')
            ->where('is_active', true)
            ->get();

        return view('crimes.edit', compact('crime', 'crimeTypes', 'offenseTypes', 'barangays', 'policeOfficers', 'officers'));
    }

    public function update(Request $request, Crime $crime): RedirectResponse
    {
        $this->authorizeEdit($crime);

        if (Auth::user()->isInvestigator()) {
            $validated = $this->validateCrimePayload($request, [
                'status' => ['required', Rule::in(Crime::STATUSES)],
                'status_notes' => 'nullable|string|max:500',
                'investigation_findings' => 'nullable|string',
                'evidence_files' => 'nullable|array',
                'evidence_files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
                'evidence_category' => ['nullable', Rule::in(array_keys(Evidence::CATEGORIES))],
                'remove_evidence_ids' => 'nullable|array',
                'remove_evidence_ids.*' => 'integer|exists:evidence,id',
            ], $crime);

            $evidenceCategory = $validated['evidence_category'] ?? 'evidence';
            $removeEvidenceIds = $validated['remove_evidence_ids'] ?? [];
            unset($validated['evidence_files'], $validated['evidence_category'], $validated['remove_evidence_ids']);

            if ($this->needsApproval($crime, $validated['status'])) {
                $this->requestApproval($crime, $validated['status'], $validated['status_notes'] ?? null);
                unset($validated['status'], $validated['status_notes']);
            }

            if (isset($validated['status']) && in_array($validated['status'], Crime::COMPLETED_STATUSES, true) && ! in_array($crime->status, Crime::COMPLETED_STATUSES, true)) {
                $validated['resolved_at'] = now();
            }

            if (($validated['investigation_findings'] ?? null) !== ($crime->investigation_findings ?? null)) {
                $validated['findings_recorded_at'] = filled($validated['investigation_findings'] ?? null) ? now() : null;
            }

            $oldStatus = $crime->status;
            $oldValues = $crime->only(array_keys($validated));
            $crime->update($validated);
            $changes = $crime->getChanges();

            if ($changes) {
                Audit::log('crime.investigation_updated', $crime, array_intersect_key($oldValues, $changes), $changes);
            }

            if (($changes['status'] ?? null) && $oldStatus !== $crime->status) {
                $this->recordStatusHistory($crime, $oldStatus, $crime->status, $validated['status_notes'] ?? null);
            }

            if ($request->hasFile('evidence_files')) {
                foreach ($request->file('evidence_files') as $file) {
                    $disk = config('filesystems.evidence_disk', 'local');
                    $path = $file->store('evidence/' . $crime->id, $disk);
                    $evidence = $crime->evidence()->create([
                        'file_path' => $path,
                        'storage_disk' => $disk,
                        'file_type' => $file->getClientMimeType(),
                        'file_name' => $file->getClientOriginalName(),
                        'category' => $evidenceCategory,
                        'uploaded_by' => Auth::id(),
                    ]);

                    Audit::log('evidence.uploaded', $evidence, [], $evidence->only([
                        'id', 'crime_id', 'file_path', 'storage_disk', 'file_name', 'file_type', 'uploaded_by',
                        'category',
                    ]));
                }
            }

            $this->archiveSelectedEvidence($crime, $removeEvidenceIds);

            return redirect()->route('crimes.show', $crime)
                ->with('success', 'Investigation details updated successfully.');
        }

        $validated = $this->validateCrimePayload($request, [
            'title' => 'required|string|max:255',
            'crime_type_id' => [
                'required',
                Rule::exists('crime_types', 'id')->where(function ($query) use ($crime) {
                    $query->where('is_active', true)->orWhere('id', $crime->crime_type_id);
                }),
            ],
            'barangay_id' => 'required|exists:barangays,id',
            'date_reported' => 'required|date|after_or_equal:date_occurred',
            'time_reported' => 'nullable|date_format:H:i',
            'date_occurred' => 'required|date',
            'time_occurred' => 'nullable|date_format:H:i',
            'stage_of_felony' => 'nullable|in:consummated,frustrated,attempted',
            'offense_type_id' => [
                'required',
                Rule::exists('offense_types', 'id')->where(function ($query) use ($crime) {
                    $query->where('is_active', true)->orWhere('id', $crime->offense_type_id);
                }),
            ],
            'latitude' => 'nullable|numeric|min:' . self::KORONADAL_BOUNDS['south'] . '|max:' . self::KORONADAL_BOUNDS['north'],
            'longitude' => 'nullable|numeric|min:' . self::KORONADAL_BOUNDS['west'] . '|max:' . self::KORONADAL_BOUNDS['east'],
            'address' => 'required|string|max:500',
            'reported_by' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'police_officer')
                    ->where('is_active', true)),
            ],
            'status' => ['required', Rule::in(Crime::STATUSES)],
            'status_notes' => 'nullable|string|max:500',
            'investigation_findings' => 'nullable|string',
            'assigned_officer' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'investigator')
                    ->where('is_active', true)),
            ],
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'evidence_category' => ['nullable', Rule::in(array_keys(Evidence::CATEGORIES))],
            'remove_evidence_ids' => 'nullable|array',
            'remove_evidence_ids.*' => 'integer|exists:evidence,id',
        ], $crime);

        $evidenceCategory = $validated['evidence_category'] ?? 'evidence';
        $removeEvidenceIds = $validated['remove_evidence_ids'] ?? [];
        unset($validated['evidence_files'], $validated['evidence_category'], $validated['remove_evidence_ids']);

        if ($this->needsApproval($crime, $validated['status'])) {
            $this->requestApproval($crime, $validated['status'], $validated['status_notes'] ?? null);
            unset($validated['status'], $validated['status_notes'], $validated['resolved_at']);
        }

        if (isset($validated['status']) && in_array($validated['status'], Crime::COMPLETED_STATUSES, true) && ! in_array($crime->status, Crime::COMPLETED_STATUSES, true)) {
            $validated['resolved_at'] = now();
        }

        if (! Auth::user()->isAdmin()) {
            unset($validated['reported_by']);
        }

        if (Auth::user()->isInvestigator()) {
            unset($validated['assigned_officer']);
        }

        if (($validated['investigation_findings'] ?? null) !== ($crime->investigation_findings ?? null)) {
            $validated['findings_recorded_at'] = filled($validated['investigation_findings'] ?? null) ? now() : null;
        }

        $this->syncBarangayFromCoordinates($validated);

        $oldStatus = $crime->status;
        $oldAssignedOfficer = $crime->assigned_officer;
        $oldValues = $crime->only(array_keys($validated));
        $crime->update($validated);
        $changes = $crime->getChanges();

        if ($changes) {
            Audit::log('crime.updated', $crime, array_intersect_key($oldValues, $changes), $changes);
        }

        if (($changes['status'] ?? null) && $oldStatus !== $crime->status) {
            $this->recordStatusHistory($crime, $oldStatus, $crime->status, $validated['status_notes'] ?? null);
        }

        if (array_key_exists('assigned_officer', $changes)) {
            $this->recordAssignmentHistory($crime, $oldAssignedOfficer, $crime->assigned_officer, 'Case assignment updated.');
            $this->notifyAssignedInvestigator($crime);
        }

        // Handle evidence upload
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                $disk = config('filesystems.evidence_disk', 'local');
                $path = $file->store('evidence/' . $crime->id, $disk);
                $evidence = $crime->evidence()->create([
                    'file_path' => $path,
                    'storage_disk' => $disk,
                    'file_type' => $file->getClientMimeType(),
                    'file_name' => $file->getClientOriginalName(),
                    'category' => $evidenceCategory,
                    'uploaded_by' => Auth::id(),
                ]);

                Audit::log('evidence.uploaded', $evidence, [], $evidence->only([
                    'id', 'crime_id', 'file_path', 'storage_disk', 'file_name', 'file_type', 'uploaded_by',
                    'category',
                ]));
            }
        }

        $this->archiveSelectedEvidence($crime, $removeEvidenceIds);

        return redirect()->route('crimes.show', $crime)
            ->with('success', 'Crime incident updated successfully.');
    }

    public function destroy(Crime $crime): RedirectResponse
    {
        $this->authorizeEdit($crime);
        abort_unless(Auth::user()->isAdmin(), 403);

        $oldValues = $crime->only(['id', 'case_number', 'title', 'status', 'reported_by', 'assigned_officer']);
        $crime->delete();
        Audit::log('crime.archived', $crime, $oldValues, [
            'archived_at' => now(),
        ]);

        return redirect()->route('crimes.index')
            ->with('success', 'Crime incident archived successfully.');
    }

    public function updateStatus(Request $request, Crime $crime): RedirectResponse
    {
        $this->authorizeEdit($crime);

        $this->validateCrimePayload($request, [
            'status' => ['required', Rule::in(Crime::STATUSES)],
            'status_notes' => 'required|string|max:500',
            'investigation_findings' => 'nullable|string',
        ]);

        if ($this->needsApproval($crime, $request->status)) {
            $this->requestApproval($crime, $request->status, $request->status_notes);

            return redirect()->back()->with('success', 'Final status request sent for admin approval.');
        }

        $data = [
            'status' => $request->status,
            'status_notes' => $request->status_notes,
            'approval_status' => 'none',
            'approval_requested_status' => null,
            'approval_notes' => null,
            'approval_requested_by' => null,
            'approval_requested_at' => null,
        ];

        if ($request->has('investigation_findings')) {
            $data['investigation_findings'] = $request->investigation_findings;

            if (($data['investigation_findings'] ?? null) !== ($crime->investigation_findings ?? null)) {
                $data['findings_recorded_at'] = filled($data['investigation_findings'] ?? null) ? now() : null;
            }
        }

        if (in_array($request->status, Crime::COMPLETED_STATUSES, true)) {
            $data['resolved_at'] = now();
        }

        $oldStatus = $crime->status;
        $oldValues = $crime->only(array_keys($data));
        $crime->update($data);
        $changes = $crime->getChanges();

        if ($changes) {
            Audit::log('crime.status_updated', $crime, array_intersect_key($oldValues, $changes), $changes);
        }

        if (($changes['status'] ?? null) && $oldStatus !== $crime->status) {
            $this->recordStatusHistory($crime, $oldStatus, $crime->status, $data['status_notes'] ?? null);
        }

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function approve(Request $request, Crime $crime): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'approval_notes' => 'nullable|string|max:500',
        ]);

        abort_unless($crime->approval_status === 'pending' && $crime->approval_requested_status, 422);

        $approvalRequesterId = $crime->approval_requested_by;

        if ($validated['decision'] === 'rejected') {
            $crime->update([
                'approval_status' => 'rejected',
                'approval_notes' => $validated['approval_notes'] ?? null,
                'approval_requested_status' => null,
                'approval_requested_by' => null,
                'approval_requested_at' => null,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->notifyUser($approvalRequesterId, $crime, 'approval_rejected', 'Case approval rejected', $crime->case_number . ' was not approved for final status.');
            Audit::log('crime.approval_rejected', $crime, [], $crime->only(['id', 'case_number', 'approval_status', 'approval_notes']));

            return back()->with('success', 'Approval request rejected.');
        }

        $oldStatus = $crime->status;
        $newStatus = $crime->approval_requested_status;
        $crime->update([
            'status' => $newStatus,
            'status_notes' => $crime->approval_notes,
            'approval_status' => 'approved',
            'approval_notes' => $validated['approval_notes'] ?? $crime->approval_notes,
            'approval_requested_status' => null,
            'approval_requested_by' => null,
            'approval_requested_at' => null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'resolved_at' => in_array($newStatus, Crime::COMPLETED_STATUSES, true) ? now() : $crime->resolved_at,
        ]);

        $this->recordStatusHistory($crime, $oldStatus, $newStatus, $validated['approval_notes'] ?? 'Final status approved by admin.');
        $this->notifyUser($approvalRequesterId, $crime, 'approval_approved', 'Case approval approved', $crime->case_number . ' was approved for ' . $crime->status_label . '.');
        Audit::log('crime.approval_approved', $crime, ['status' => $oldStatus], $crime->only(['id', 'case_number', 'status', 'approval_status', 'approved_by']));

        return back()->with('success', 'Final status approved.');
    }

    public function importForm(): View
    {
        return view('crimes.import');
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), fgetcsv($handle) ?: []);
        $created = 0;

        DB::transaction(function () use ($handle, $headers, &$created) {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, array_pad($row, count($headers), null));
                if (! $data || blank($data['title'] ?? null)) {
                    continue;
                }

                $crimeType = CrimeType::where('name', $data['crime_type'] ?? '')->first();
                $offenseType = OffenseType::where('name', $data['offense_type'] ?? '')->first();
                $barangay = Barangay::where('name', $data['barangay'] ?? '')->first();

                if (! $crimeType || ! $offenseType || ! $barangay) {
                    continue;
                }

                $crime = Crime::create([
                    'case_number' => $data['case_number'] ?: Crime::generateCaseNumber(),
                    'title' => $data['title'],
                    'crime_type_id' => $crimeType->id,
                    'offense_type_id' => $offenseType->id,
                    'barangay_id' => $barangay->id,
                    'date_reported' => $data['date_reported'] ?: now()->toDateString(),
                    'date_occurred' => $data['date_occurred'] ?: now()->toDateString(),
                    'address' => $data['address'] ?: $barangay->name . ', Koronadal City',
                    'status' => $data['status'] && in_array($data['status'], Crime::STATUSES, true) ? $data['status'] : Crime::STATUS_PENDING,
                    'reported_by' => Auth::id(),
                    'assigned_officer' => null,
                    'latitude' => $data['latitude'] ?: null,
                    'longitude' => $data['longitude'] ?: null,
                ]);

                $this->recordStatusHistory($crime, null, $crime->status, 'Imported from existing records CSV.');
                $created++;
            }
        });

        fclose($handle);
        Audit::log('crime.imported', null, [], ['records_created' => $created]);

        return redirect()->route('crimes.index')->with('success', "{$created} crime record(s) imported.");
    }

    private function recordStatusHistory(Crime $crime, ?string $oldStatus, string $newStatus, ?string $remarks = null): void
    {
        $crime->statusHistories()->create([
            'changed_by' => Auth::id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks ?: $this->defaultStatusHistoryRemark($newStatus),
        ]);
    }

    private function recordAssignmentHistory(Crime $crime, ?int $oldUserId, ?int $newUserId, ?string $remarks = null): void
    {
        $crime->assignmentHistories()->create([
            'old_user_id' => $oldUserId,
            'new_user_id' => $newUserId,
            'changed_by' => Auth::id(),
            'remarks' => $remarks,
        ]);
    }

    private function needsApproval(Crime $crime, string $status): bool
    {
        return ! Auth::user()->isAdmin()
            && $status !== $crime->status
            && in_array($status, Crime::APPROVAL_FINAL_STATUSES, true);
    }

    private function requestApproval(Crime $crime, string $status, ?string $notes): void
    {
        $crime->update([
            'approval_status' => 'pending',
            'approval_requested_status' => $status,
            'approval_notes' => $notes,
            'approval_requested_by' => Auth::id(),
            'approval_requested_at' => now(),
        ]);

        User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get()
            ->each(fn (User $admin) => $this->notifyUser($admin->id, $crime, 'approval_request', 'Case needs approval', $crime->case_number . ' is requesting final status: ' . (Crime::STATUS_LABELS[$status] ?? $status) . '.'));

        Audit::log('crime.approval_requested', $crime, [], $crime->only(['id', 'case_number', 'approval_status', 'approval_requested_status', 'approval_requested_by']));
    }

    private function notifyAssignedInvestigator(Crime $crime): void
    {
        if ($crime->assigned_officer) {
            $this->notifyUser($crime->assigned_officer, $crime, 'case_assigned', 'Case assigned to you', $crime->case_number . ' has been assigned to you.');
        }
    }

    private function notifyUser(?int $userId, Crime $crime, string $type, string $title, string $message): void
    {
        if (! $userId) {
            return;
        }

        SystemNotification::create([
            'user_id' => $userId,
            'crime_id' => $crime->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function defaultStatusHistoryRemark(string $status): string
    {
        return match ($status) {
            Crime::STATUS_PENDING => 'Crime incident was received and is ready for mapping and documentation.',
            Crime::STATUS_CIRAS_RECORDING => 'Incident details are being encoded for CIRAS documentation.',
            Crime::STATUS_FOR_REVIEW => 'Encoded incident details are ready for summary review.',
            Crime::STATUS_FOR_CORRECTION => 'Incident details need correction before final storage.',
            Crime::STATUS_DATA_STORED => 'Reviewed crime incident data has been stored in the system records.',
            Crime::STATUS_IRF_PRINTED => 'Incident Record Form has been prepared for printing/documentation.',
            Crime::STATUS_FOR_SIGNATURE => 'Printed IRF is awaiting required signature or validation.',
            Crime::STATUS_BLOTTER_ENTERED => 'Crime incident has been entered in the police blotter record.',
            Crime::STATUS_UCPER_COMPILED => 'Completed incident data has been compiled for UCPER reporting.',
            Crime::STATUS_RESOLVED => 'Crime incident has been marked as resolved.',
            Crime::STATUS_CLOSED => 'Crime incident has been closed in the system.',
            default => 'Crime incident status was updated.',
        };
    }

    private function authorizeEdit(Crime $crime): void
    {
        $this->authorizeView($crime);
    }

    private function authorizeView(Crime $crime): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isPoliceOfficer() && $crime->reported_by === $user->id) {
            return;
        }

        if ($user->isInvestigator() && $crime->assigned_officer === $user->id) {
            return;
        }

        abort(403, 'You are not authorized to access this crime record.');
    }

    private function archiveSelectedEvidence(Crime $crime, array $evidenceIds): void
    {
        if (empty($evidenceIds)) {
            return;
        }

        $user = Auth::user();

        $crime->evidence()
            ->whereIn('id', $evidenceIds)
            ->get()
            ->each(function (Evidence $evidence) use ($user) {
                if (! $user->isAdmin() && $evidence->uploaded_by !== $user->id) {
                    return;
                }

                $oldValues = $evidence->only(['id', 'crime_id', 'file_path', 'storage_disk', 'file_name', 'file_type', 'uploaded_by', 'category']);
                $evidence->delete();
                Audit::log('evidence.archived', $evidence, $oldValues, [
                    'archived_at' => now(),
                ]);
            });
    }

    private function syncBarangayFromCoordinates(array &$validated): void
    {
        if (! filled($validated['latitude'] ?? null) || ! filled($validated['longitude'] ?? null)) {
            return;
        }

        $match = app(CrimeMapController::class)->detectBarangayByCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if (! empty($match['id'])) {
            $validated['barangay_id'] = $match['id'];

            if (
                blank($validated['address'] ?? null)
                || $this->isGenericBarangayAddress((string) $validated['address'])
            ) {
                $validated['address'] = $match['name'] . ', Koronadal City';
            }
        }
    }

    private function isGenericBarangayAddress(string $address): bool
    {
        $normalizedAddress = $this->normalizeBarangayName($address);

        if ($normalizedAddress === '') {
            return true;
        }

        return Barangay::where('city', 'Koronadal City')
            ->get(['name'])
            ->contains(function (Barangay $barangay) use ($normalizedAddress) {
                $normalizedName = $this->normalizeBarangayName($barangay->name);

                return in_array($normalizedAddress, [
                    $normalizedName,
                    $normalizedName . 'koronadalcity',
                    $normalizedName . 'cityofkoronadal',
                ], true);
            });
    }

    private function normalizeBarangayName(string $name): string
    {
        $normalized = strtolower($name);
        $normalized = preg_replace('/\([^)]*\)/', '', $normalized);
        $normalized = str_replace(["\xc3\xb1", "\xc3\x91", "\xc3\x83\xc2\xb1", "\xc3\x83\xc2\x91"], 'n', $normalized);
        $normalized = str_replace(['Ã±', 'Ã‘'], 'n', $normalized);
        $normalized = preg_replace('/\bbarangay\b|\bbrgy\.?\b/', '', $normalized);
        $normalized = preg_replace('/\bsto\.?\b/', 'santo', $normalized);
        $normalized = preg_replace('/\bsta\.?\b/', 'santa', $normalized);
        $normalized = preg_replace('/\bzone\s*iv\b/', 'zone4', $normalized);
        $normalized = preg_replace('/\bzone\s*iii\b/', 'zone3', $normalized);
        $normalized = preg_replace('/\bzone\s*ii\b/', 'zone2', $normalized);
        $normalized = preg_replace('/\bzone\s*i\b/', 'zone1', $normalized);
        $normalized = preg_replace('/\bzone\s*one\b/', 'zone1', $normalized);
        $normalized = preg_replace('/\bzone\s*two\b/', 'zone2', $normalized);
        $normalized = preg_replace('/\bzone\s*three\b/', 'zone3', $normalized);
        $normalized = preg_replace('/\bzone\s*four\b/', 'zone4', $normalized);

        return preg_replace('/[^a-z0-9]+/', '', $normalized) ?? '';
    }

    private function validateCrimePayload(Request $request, array $rules, ?Crime $crime = null): array
    {
        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request, $crime) {
            if (
                $request->filled(['date_reported', 'date_occurred', 'time_reported', 'time_occurred'])
                && $request->date_reported === $request->date_occurred
                && $request->time_reported < $request->time_occurred
            ) {
                $validator->errors()->add('time_reported', 'The time reported must be after or equal to the time committed when both dates are the same.');
            }

            if ($crime && $request->filled('status') && $request->status !== $crime->status && ! $request->filled('status_notes')) {
                $validator->errors()->add('status_notes', 'Remarks are required when changing the workflow status.');
            }
        });

        return $validator->validate();
    }
}
