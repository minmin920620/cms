@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
@php
    $fieldLabels = [
        'status' => 'Case Status',
        'status_notes' => 'Status Remarks',
        'title' => 'Incident Title',
        'case_number' => 'Case Number',
        'crime_type_id' => 'Crime Type ID',
        'offense_type_id' => 'Offense Type ID',
        'barangay_id' => 'Barangay ID',
        'reported_by' => 'Reported By ID',
        'assigned_officer' => 'Assigned Investigator ID',
        'investigation_findings' => 'Investigation Findings',
        'findings_recorded_at' => 'Findings Recorded At',
        'updated_at' => 'Updated At',
        'archived_at' => 'Archived At',
    ];

    $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A');

    $formatAuditValue = function ($field, $value) {
        if ($value === null || $value === '') {
            return 'None';
        }

        if ($field === 'status') {
            return \App\Models\Crime::STATUS_LABELS[$value] ?? ucfirst(str_replace('_', ' ', (string) $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    };
@endphp

<div class="space-y-6">
    <div class="material-card p-5">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Case Number</label>
                <input type="text" name="case_number" value="{{ request('case_number') }}" placeholder="CRM-..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Action</label>
                <select name="action" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst(str_replace(['_', '.'], ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">User</label>
                <select name="user_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">User Role</label>
                <select name="user_role" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ request('user_role') === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Record Type</label>
                <select name="record_type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">All Records</option>
                    @foreach($recordTypes as $recordType)
                        <option value="{{ $recordType }}" {{ request('record_type') === $recordType ? 'selected' : '' }}>{{ $recordType }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Filter</button>
                <a href="{{ route('audit-logs.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="material-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date / Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Changes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($auditLogs as $log)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $displayTime($log->created_at) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <p class="font-medium text-slate-900">{{ $log->user->name ?? 'System' }}</p>
                                <p class="text-xs text-slate-500">{{ $log->user->email ?? 'No user account' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ ucfirst(str_replace(['_', '.'], ' ', $log->action)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p>{{ class_basename($log->auditable_type) ?: 'N/A' }}</p>
                                <p class="text-xs font-mono text-slate-500">ID: {{ $log->auditable_id ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 min-w-[320px]">
                                @if($log->old_values)
                                    <details class="mb-2">
                                        <summary class="cursor-pointer font-semibold text-slate-700">Before Update</summary>
                                        <div class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white">
                                            @foreach($log->old_values as $field => $value)
                                                <div class="grid grid-cols-[120px_1fr] gap-2 border-b border-slate-100 px-3 py-2 last:border-b-0">
                                                    <span class="font-semibold text-slate-500">{{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}</span>
                                                    <span class="break-words text-slate-800">{{ $formatAuditValue($field, $value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif

                                @if($log->new_values)
                                    <details>
                                        <summary class="cursor-pointer font-semibold text-slate-700">After Update</summary>
                                        <div class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white">
                                            @foreach($log->new_values as $field => $value)
                                                <div class="grid grid-cols-[120px_1fr] gap-2 border-b border-slate-100 px-3 py-2 last:border-b-0">
                                                    <span class="font-semibold text-slate-500">{{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}</span>
                                                    <span class="break-words text-slate-800">{{ $formatAuditValue($field, $value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif

                                @if(!$log->old_values && !$log->new_values)
                                    <span class="text-slate-400">No field details recorded</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <p class="font-mono">{{ $log->ip_address ?? 'N/A' }}</p>
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs font-medium text-slate-500">Browser details</summary>
                                    <p class="mt-1 max-w-[260px] break-words text-xs text-slate-400">{{ $log->user_agent ?? 'No browser details' }}</p>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auditLogs->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
