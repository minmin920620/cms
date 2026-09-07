@extends('layouts.app')

@section('title', 'Edit Offense Type')
@section('page_title', 'Edit Offense Type')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="material-card p-6">
        <form method="POST" action="{{ route('offense-types.update', $offenseType) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Offense Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $offenseType->name) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="crime_type_id" class="block text-sm font-medium text-gray-700 mb-1">Related Crime Type</label>
                    <select id="crime_type_id" name="crime_type_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Unlinked</option>
                        @foreach($crimeTypes as $type)
                            <option value="{{ $type->id }}" {{ old('crime_type_id', $offenseType->crime_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('crime_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $offenseType->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('offense-types.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Update Offense Type</button>
            </div>
        </form>
    </div>
</div>
@endsection
