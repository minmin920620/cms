<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function show(Evidence $evidence): StreamedResponse|Response
    {
        $this->authorizeEvidenceAccess($evidence);

        $disk = $evidence->storage_disk ?: 'public';
        $path = $evidence->file_path;

        if (! Storage::disk($disk)->exists($path) && $disk !== 'public') {
            $disk = 'public';
        }

        abort_unless(Storage::disk($disk)->exists($path), 404, 'Evidence file not found.');

        $mimeType = Storage::disk($disk)->mimeType($path) ?: $evidence->file_type ?: 'application/octet-stream';
        $fileName = str_replace('"', '', $evidence->file_name ?: basename($path));

        return Storage::disk($disk)->response($path, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function destroy(Evidence $evidence): RedirectResponse
    {
        $this->authorizeEvidenceArchive($evidence);

        $oldValues = $evidence->only(['id', 'crime_id', 'file_path', 'storage_disk', 'file_name', 'file_type', 'uploaded_by']);
        $evidence->delete();
        Audit::log('evidence.archived', $evidence, $oldValues, [
            'archived_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Evidence archived successfully.');
    }

    private function authorizeEvidenceAccess(Evidence $evidence): void
    {
        $user = Auth::user();
        $crime = $evidence->crime;

        if ($user->isAdmin()) {
            return;
        }

        if ($evidence->uploaded_by === $user->id) {
            return;
        }

        if ($crime && ($crime->reported_by === $user->id || $crime->assigned_officer === $user->id)) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }

    private function authorizeEvidenceArchive(Evidence $evidence): void
    {
        $user = Auth::user();

        if ($user->isAdmin() || $evidence->uploaded_by === $user->id) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}

