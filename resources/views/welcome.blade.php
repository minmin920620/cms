<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="0;url={{ auth()->check() ? route('dashboard') : route('login') }}">
        <title>{{ config('app.name', 'Crime Mapping System') }}</title>
    </head>
    <body>
        <script>
            window.location.replace(@json(auth()->check() ? route('dashboard') : route('login')));
        </script>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">
            Continue to {{ auth()->check() ? 'dashboard' : 'login' }}
        </a>
    </body>
</html>
