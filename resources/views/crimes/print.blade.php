<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case File {{ $crime->case_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; font-size: 13px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { border-bottom: 1px solid #d1d5db; font-size: 15px; margin: 24px 0 10px; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 28%; }
        .header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #111827; padding-bottom: 14px; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; border: 1px solid #9ca3af; border-radius: 999px; padding: 3px 8px; font-size: 12px; }
        .findings p { margin: 0 0 8px; }
        .findings ul { margin: 0 0 8px 18px; padding: 0; }
        .findings li { margin-bottom: 4px; }
        .print-button { margin-bottom: 20px; }
        .print-button button { background: #111827; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: 9px 14px; }
        @media print {
            body { margin: 18mm; }
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    @php
        $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A');
    @endphp

    <div class="print-button">
        <button onclick="window.print()">Print Case File</button>
    </div>

    <div class="header">
        <div>
            <h1>Crime Incident Case File</h1>
            <div class="muted">Web-Based Crime Mapping System</div>
        </div>
        <div>
            <strong>{{ $crime->case_number }}</strong><br>
            <span class="badge">{{ $crime->status_label }}</span>
        </div>
    </div>

    <h2>Incident Details</h2>
    <table>
        <tr><th>Title</th><td>{{ $crime->title }}</td></tr>
        <tr><th>Crime Type</th><td>{{ $crime->crimeType->name ?? 'N/A' }}</td></tr>
        <tr><th>Offense</th><td>{{ $crime->offenseType->name ?? 'N/A' }}</td></tr>
        <tr><th>Stage of Felony</th><td>{{ $crime->stage_of_felony ? ucfirst($crime->stage_of_felony) : 'N/A' }}</td></tr>
        <tr><th>Date Reported</th><td>{{ $crime->date_reported ? $crime->date_reported->format('F d, Y') : 'N/A' }} {{ $crime->time_reported ? $crime->time_reported->format('h:i A') : '' }}</td></tr>
        <tr><th>Date Committed</th><td>{{ $crime->date_occurred ? $crime->date_occurred->format('F d, Y') : 'N/A' }} {{ $crime->time_occurred ? $crime->time_occurred->format('h:i A') : '' }}</td></tr>
        <tr><th>Barangay</th><td>{{ $crime->barangay->name ?? 'N/A' }}</td></tr>
        <tr><th>Address</th><td>{{ $crime->address ?? 'N/A' }}</td></tr>
        <tr><th>Coordinates</th><td>{{ $crime->latitude && $crime->longitude ? $crime->latitude . ', ' . $crime->longitude : 'N/A' }}</td></tr>
    </table>

    <h2>Personnel</h2>
    <table>
        <tr><th>Reported By</th><td>{{ $crime->reporter->name ?? 'Unknown' }}</td></tr>
        <tr><th>Assigned Investigator</th><td>{{ $crime->assignedOfficer->name ?? 'Unassigned' }}</td></tr>
    </table>

    <h2>Investigation Findings</h2>
    <div class="findings">{!! $crime->formatted_investigation_findings !!}</div>

    <h2>Evidence And Documents</h2>
    <table>
        <tr><th>Category</th><th>File Name</th><th>Uploaded By</th><th>Date Uploaded</th></tr>
        @forelse($crime->evidence as $item)
            <tr>
                <td>{{ $item->category_label }}</td>
                <td>{{ $item->file_name }}</td>
                <td>{{ $item->uploadedBy->name ?? 'Unknown' }}</td>
                <td>{{ $displayTime($item->created_at) ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No evidence or proof documents uploaded.</td></tr>
        @endforelse
    </table>

    <h2>Workflow History</h2>
    <table>
        <tr><th>Status</th><th>Changed By</th><th>Date</th><th>Remarks</th></tr>
        @forelse($crime->statusHistories as $history)
            <tr>
                <td>{{ $history->new_status_label }}</td>
                <td>{{ $history->changedBy->name ?? 'System' }}</td>
                <td>{{ $displayTime($history->created_at) }}</td>
                <td>{!! nl2br(e($history->remarks ?: 'N/A')) !!}</td>
            </tr>
        @empty
            <tr><td colspan="4">No workflow history recorded.</td></tr>
        @endforelse
    </table>
</body>
</html>
