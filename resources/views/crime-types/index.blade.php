@extends('layouts.app')

@section('title', 'Crime Types')
@section('page_title', 'Crime Types')

@section('content')
<div class="material-card overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Manage incident categories used in reports, maps, and analytics.</p>
        <a href="{{ route('crime-types.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Add Crime Type</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Incidents</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($crimeTypes as $type)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="h-4 w-4 rounded-full border border-gray-200" style="background-color: {{ $type->color }}"></span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $type->name }}</p>
                                    @if($type->is_active && $type->offense_types_count === 0)
                                        <p class="mt-1 text-xs font-medium text-amber-600">No linked offense type</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <div>{{ $type->crimes_count }}</div>
                            <div class="mt-1 text-xs text-gray-400">{{ $type->offense_types_count }} offense link(s)</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($type->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('crime-types.edit', $type) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                                @if($type->is_active)
                                    <form method="POST" action="{{ route('crime-types.destroy', $type) }}" onsubmit="return confirm('Archive this crime type? It will be hidden from future incident selection but kept for existing records.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Archive</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            <p class="text-lg font-medium mb-1">No crime types found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200">
        {{ $crimeTypes->links() }}
    </div>
</div>
@endsection
