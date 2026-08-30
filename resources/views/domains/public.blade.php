<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{--
        No CSRF meta here. This domain's GET routes run without a session, because a
        Set-Cookie header would stop the response being cacheable.

        Careful with the title and description below. Svelte's head block appends
        rather than replaces, so when SSR is not running the page ends up with two of
        each and the browser uses these. Fine while both say the same thing; revisit in
        Phase 14, which adds per-page SEO and Open Graph tags plus GTM with Consent
        Mode v2.
    --}}

    <x-inertia::head>
        <title>{{ config('app.name') }}</title>
        <meta name="description" content="{{ config('app.name') }}">
    </x-inertia::head>

    @vite(['resources/scss/app.scss', 'resources/ts/public.ts'])
</head>
<body>
@inertia
</body>
</html>
