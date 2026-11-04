<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#c99555">
        <meta name="description" content="{{ \App\Models\Setting::map()['hero_subtitle'] ?? '' }}">
        <title inertia>{{ \App\Models\Setting::map()['company_name'] ?? config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
