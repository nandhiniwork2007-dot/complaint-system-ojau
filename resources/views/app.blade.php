<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        {{-- app icon --}}
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/x-icon">
        <title>{{ config('app.name', 'Career Next') }}</title>
        <script>
            (() => {
                try {
                    const preference = localStorage.getItem('career-next-theme') || 'system';
                    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const theme = preference === 'system' ? (systemDark ? 'dark' : 'light') : preference;
                    document.documentElement.dataset.theme = theme;
                    document.documentElement.dataset.themePreference = preference;
                    document.documentElement.style.colorScheme = theme;
                } catch (error) {
                    document.documentElement.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    document.documentElement.dataset.themePreference = 'system';
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
