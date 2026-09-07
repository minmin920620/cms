@extends('layouts.app')

@section('title', 'Notifications')
@section('page_title', 'Notifications')

@section('content')
@php
    $displayTime = fn ($date) => $date?->copy()->timezone(config('app.timezone'))->format('M d, Y h:i A');
@endphp

<div class="material-card overflow-hidden">
    <div class="divide-y divide-slate-100">
        @forelse($notifications as $notification)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/50' }}">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $notification->message }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ $displayTime($notification->created_at) }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if($notification->crime)
                        <a href="{{ route('crimes.show', $notification->crime) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Open Case</a>
                    @endif
                    @unless($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Mark Read</button>
                        </form>
                    @endunless
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-sm text-slate-500">No notifications yet.</div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
