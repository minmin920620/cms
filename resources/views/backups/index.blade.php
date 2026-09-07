@extends('layouts.app')

@section('title', 'Backup')
@section('page_title', 'Backup and Restore')

@section('content')
<div class="max-w-3xl">
    <div class="material-card p-6">
        <h2 class="text-lg font-semibold text-slate-900">Database Backup</h2>
        <p class="mt-2 text-sm text-slate-600">Download a timestamped SQL backup of the current database records for safekeeping.</p>
        <a href="{{ route('backups.download') }}" class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
            Download Backup
        </a>
    </div>

    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Restore is intentionally not automatic here because it can overwrite active records. Keep downloaded backups for recovery by the system administrator or database maintainer.
    </div>
</div>
@endsection
