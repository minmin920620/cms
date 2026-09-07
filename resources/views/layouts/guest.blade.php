<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Crime Mapping System') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/koronadal-official-seal-square.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            svg[class~="h-4"][class~="w-4"] { width: 1rem !important; height: 1rem !important; }
            svg[class~="h-5"][class~="w-5"] { width: 1.25rem !important; height: 1.25rem !important; }
            svg[class~="h-6"][class~="w-6"] { width: 1.5rem !important; height: 1.5rem !important; }
            svg[class~="h-7"][class~="w-7"] { width: 1.75rem !important; height: 1.75rem !important; }
            svg[class~="h-8"][class~="w-8"] { width: 2rem !important; height: 2rem !important; }
            svg[class~="h-12"][class~="w-12"] { width: 3rem !important; height: 3rem !important; }
            img[class~="h-12"][class~="w-12"] { width: 3rem !important; height: 3rem !important; }
            img[class~="h-16"][class~="w-16"] { width: 4rem !important; height: 4rem !important; }
        </style>
    </head>
    <body class="font-sans antialiased text-foreground">
        <div class="material-shell min-h-screen">
            <div class="material-content flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid w-full max-w-6xl overflow-hidden rounded-lg border border-border bg-card shadow-2xl shadow-slate-900/10 lg:grid-cols-2">
                    <section class="relative flex min-h-[520px] items-center justify-center overflow-hidden bg-slate-900 p-8 text-center text-white sm:p-10">
                        <img src="{{ asset('images/koronadal-city-aerial.webp') }}" alt="Aerial view of Koronadal City" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-slate-950/65"></div>

                        <div class="relative flex max-w-xl flex-col items-center gap-5">
                            <x-application-logo class="h-16 w-16 text-white drop-shadow" />
                            <h1 class="text-4xl font-extrabold leading-tight text-white sm:text-5xl" style="text-shadow: 0 4px 14px rgba(0, 0, 0, 0.95), 0 2px 4px rgba(0, 0, 0, 0.9);">
                                City of Koronadal<br>
                                <span class="uppercase">CRIME MAPPING SYSTEM</span>
                            </h1>
                        </div>
                    </section>

                    <section class="flex items-center bg-background p-6 sm:p-10">
                        <div class="w-full">
                            <div class="mb-6">
                                <h2 class="text-2xl font-bold text-foreground">Sign in</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Use your crime mapping account credentials.</p>
                            </div>

                            <div class="rounded-lg bg-card p-6">
                                {{ $slot }}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>
