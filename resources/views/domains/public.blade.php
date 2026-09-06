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


    {{--
        No CSRF meta here. This domain's GET routes run without a session, because a
        Set-Cookie header would stop the response being cacheable.

        Be careful with the title and description below. Svelte's head block appends
        rather than replaces, so when SSR is not running the page ends up with two of
        each and the browser uses these. That is fine while both say the same thing.
        Revisit it once pages set their own SEO and Open Graph tags.
    --}}

    <x-inertia::head>
        <title>{{ config('app.name') }}</title>
        <meta name="description" content="{{ config('app.name') }}">
    </x-inertia::head>

    @vite(['resources/scss/app.scss', 'resources/ts/domains/public/entry.ts'])
</head>
<body class="marketing">
@inertia
</body>
</html>
