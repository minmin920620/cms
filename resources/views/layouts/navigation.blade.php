@php
    $user = Auth::user();
    $assignedInvestigationCount = $user?->isInvestigator()
        ? $user->assignedCrimes()->whereIn('status', \App\Models\Crime::OPEN_STATUSES)->count()
        : 0;
    $unreadNotificationCount = $user ? $user->notifications()->whereNull('read_at')->count() : 0;
    $navItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        ],
        [
            'label' => 'Crime Incidents',
            'route' => 'crimes.index',
            'active' => request()->routeIs('crimes.*'),
            'roles' => ['admin', 'police_officer', 'investigator'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />',
        ],
        [
            'label' => 'Crime Map',
            'route' => 'map.index',
            'active' => request()->routeIs('map.*'),
            'roles' => ['admin', 'police_officer', 'investigator'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />',
        ],
        [
            'label' => 'Hotspots',
            'route' => 'hotspots.index',
            'active' => request()->routeIs('hotspots.*'),
            'roles' => ['admin', 'police_officer', 'investigator'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />',
        ],
        [
            'label' => 'Analytics',
            'route' => 'analytics.index',
            'active' => request()->routeIs('analytics.*'),
            'roles' => ['admin', 'lgu'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        ],
        [
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'active' => request()->routeIs('notifications.*'),
            'roles' => ['admin', 'police_officer', 'investigator'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />',
        ],
        [
            'label' => 'User Management',
            'route' => 'users.index',
            'active' => request()->routeIs('users.*'),
            'admin' => true,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />',
        ],
        [
            'label' => 'Crime Types',
            'route' => 'crime-types.index',
            'active' => request()->routeIs('crime-types.*'),
            'admin' => true,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />',
        ],
        [
            'label' => 'Offense Types',
            'route' => 'offense-types.index',
            'active' => request()->routeIs('offense-types.*'),
            'admin' => true,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m-6-8h6M5 5a2 2 0 012-2h6l6 6v10a2 2 0 01-2 2H7a2 2 0 01-2-2V5z" />',
        ],
        [
            'label' => 'Reports',
            'route' => 'reports.index',
            'active' => request()->routeIs('reports.*'),
            'admin' => true,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />',
        ],
        [
            'label' => 'Audit Logs',
            'route' => 'audit-logs.index',
            'active' => request()->routeIs('audit-logs.*'),
            'admin' => true,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
    ];
@endphp

<aside id="mainSidebar" class="-translate-x-full lg:translate-x-0 fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl shadow-slate-900/5 transition-transform duration-300 lg:static lg:h-full">
    {{-- City Branding Header --}}
    <div class="flex h-20 items-center gap-3 border-b border-sidebar-border px-5">
        <img
            src="{{ asset('images/koronadal-official-seal-square.png') }}"
            alt="City of Koronadal Official Seal"
            class="h-12 w-12 shrink-0 rounded-full object-contain shadow-lg shadow-slate-900/10"
        >
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">Republic of the Philippines</p>
            <p class="text-base font-bold leading-tight">City of Koronadal</p>
            <p class="text-[11px] text-muted-foreground">Crime Mapping System</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @foreach ($navItems as $item)
            @continue(($item['admin'] ?? false) && !$user?->isAdmin())
            @continue(isset($item['roles']) && !$user?->hasAnyRole($item['roles']))
            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-3 rounded-lg border px-4 py-3 text-sm font-medium transition-all duration-150
                      {{ $item['active']
                          ? 'border-sidebar-primary bg-sidebar-primary text-sidebar-primary-foreground shadow-sm'
                          : 'border-transparent text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-sidebar-primary-foreground' : 'text-muted-foreground group-hover:text-sidebar-accent-foreground' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $item['label'] }}</span>
                @if($item['route'] === 'crimes.index' && $assignedInvestigationCount > 0)
                    <span class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                        {{ $assignedInvestigationCount }}
                    </span>
                @endif
                @if($item['route'] === 'notifications.index' && $unreadNotificationCount > 0)
                    <span class="ml-auto rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">
                        {{ $unreadNotificationCount }}
                    </span>
                @endif
                @if($item['active'])
                    <span class="{{ $item['route'] === 'crimes.index' && $assignedInvestigationCount > 0 ? '' : 'ml-auto' }} h-2 w-2 rounded-full bg-sidebar-primary-foreground"></span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Bottom actions --}}
    <div class="space-y-1 border-t border-sidebar-border p-3">
        <a href="{{ route('profile.edit') }}"
           class="group flex items-center gap-3 rounded-lg border px-4 py-2.5 text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('profile.*') ? 'border-sidebar-primary bg-sidebar-primary text-sidebar-primary-foreground' : 'border-transparent text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.607 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Account Settings</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg px-4 py-2.5 text-left text-sm font-medium text-muted-foreground transition-all duration-150 hover:bg-red-50 hover:text-red-600">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

{{-- Mobile overlay --}}
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/60 lg:hidden" onclick="document.getElementById('mainSidebar').classList.toggle('-translate-x-full'); document.getElementById('mainSidebar').classList.toggle('translate-x-0'); this.classList.toggle('hidden');"></div>
