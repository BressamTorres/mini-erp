<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} | Infinit ERP</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 sm:px-6">
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-violet-200/40 blur-3xl"></div>
            <div class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-indigo-200/40 blur-3xl"></div>

            <div @class([
                'relative w-full',
                'max-w-md overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-6 py-9 shadow-xl shadow-slate-200/70 sm:px-10 sm:py-11' => ! $contentOnly,
            ])>
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
