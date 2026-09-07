<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS') – City of Koronadal Crime Mapping System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/koronadal-official-seal-square.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @stack('styles')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="material-shell min-h-screen lg:flex">
        @include('layouts.navigation')

        <div class="material-content min-w-0 flex-1">
            {{-- Top Header Bar --}}
            <header class="sticky top-0 z-20 border-b border-border bg-card/90 backdrop-blur-xl">
                <div class="flex min-h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        {{-- Mobile hamburger --}}
                        <button type="button" id="mobileMenuToggle" class="lg:hidden rounded-lg border border-border bg-background p-2 text-muted-foreground shadow-sm hover:bg-accent hover:text-accent-foreground">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">City of Koronadal</p>
                            <h1 class="text-xl font-bold text-foreground leading-tight">
                                @yield('page_title')
                                @isset($header){{ $header }}@endisset
                            </h1>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-foreground">{{ Auth::user()->name ?? '' }}</p>
                            <p class="text-xs font-medium text-muted-foreground capitalize">{{ Auth::user()->role === 'lgu' ? 'LGU' : str_replace('_', ' ', Auth::user()->role ?? '') }}</p>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-sm font-bold shadow-sm">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800 shadow-sm">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('info') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
                {{ $slot ?? '' }}
            </main>

            {{-- Footer --}}
            <footer class="border-t border-border bg-card/80 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-muted-foreground">
                    <p>&copy; {{ date('Y') }} City of polomolok. All rights reserved.</p>
                    <p>Crime Mapping &amp; Information System v1.0</p>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Mobile menu toggle --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function () {
                    sidebar.classList.toggle('-translate-x-full');
                    sidebar.classList.toggle('translate-x-0');
                    if (overlay) overlay.classList.toggle('hidden');
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
