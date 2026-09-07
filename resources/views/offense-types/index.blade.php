@extends('layouts.app')

@section('title', 'Offense Types')
@section('page_title', 'Offense Types')

@section('content')
<div class="material-card overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Manage the offense choices available when reporting an incident.</p>
        <a href="{{ route('offense-types.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Add Offense Type</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Offense</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crime Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($offenseTypes as $offense)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $offense->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($offense->crimeType)
                                {{ $offense->crimeType->name }}
                            @else
                                <a href="{{ route('offense-types.edit', $offense) }}" class="font-medium text-amber-700 hover:text-amber-800">Needs linking</a>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($offense->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('offense-types.edit', $offense) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                                @if($offense->is_active)
                                    <form method="POST" action="{{ route('offense-types.destroy', $offense) }}" onsubmit="return confirm('Archive this offense type? Existing incident records will keep their saved offense reference.');">
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
                            <p class="text-lg font-medium mb-1">No offense types found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200">
        {{ $offenseTypes->links() }}
    </div>
</div>
@endsection
