@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
<div class="material-card overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Manage system users and their roles</p>
        <a href="{{ route('users.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Add User</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Badge #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                    <span class="text-sm font-semibold text-red-600">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @php
                                $roleColors = [
                                    'admin' => 'bg-purple-100 text-purple-800',
                                    'police_officer' => 'bg-blue-100 text-blue-800',
                                    'investigator' => 'bg-green-100 text-green-800',
                                    'lgu' => 'bg-amber-100 text-amber-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleColors[$user->role] ?? 'bg-gray-100' }}">
                                {{ $user->role === 'lgu' ? 'LGU' : ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $user->badge_number ?? 'N/A' }}</td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Archive this user? The account will be deactivated but its records will be kept.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <p class="text-lg font-medium mb-1">No users found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $users->links() }}
    </div>
</div>
@endsection

