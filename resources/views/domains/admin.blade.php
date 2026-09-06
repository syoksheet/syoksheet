<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Preload the one font every page uses, so text does not repaint when Geist
         arrives. crossorigin is required even same-origin: fonts are fetched in CORS
         mode, and without it the browser downloads the file twice.

         Sans only. Preloading mono or italic would download files most pages never
         use, and the browser warns about preloads it does not consume. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ Vite::asset('resources/fonts/Geist-Variable.woff2') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- The admin panel is behind a login, so it should never be indexed. --}}
    <meta name="robots" content="noindex, nofollow">
    <title inertia>{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/ts/domains/admin/entry.ts'])
    @inertiaHead
</head>
<body>
@inertia
</body>
</html>
