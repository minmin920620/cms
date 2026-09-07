@extends('layouts.app')

@section('title', 'Import Crime Records')
@section('page_title', 'Import Crime Records')

@section('content')
<div class="max-w-3xl">
    <div class="material-card p-6">
        <form method="POST" action="{{ route('crimes.import') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">CSV File</label>
                <input type="file" name="csv_file" required accept=".csv,text/csv" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900">
                @error('csv_file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Required headers: <span class="font-mono">title, crime_type, offense_type, barangay</span>.
                Optional headers: <span class="font-mono">case_number, date_reported, date_occurred, address, status, latitude, longitude</span>.
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <a href="{{ route('crimes.index') }}" class="text-sm font-medium text-slate-600">Cancel</a>
                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Import Records</button>
            </div>
        </form>
    </div>
</div>
@endsection
