<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ url('/') }}" class="text-sm font-semibold tracking-tight text-slate-900">{{ config('app.name', 'App') }}</a>
            <div class="text-xs text-slate-400">
                Developed by <a href="https://imrandev.bd/" class="underline hover:text-slate-600">Imran Dev BD</a>
                · <a href="https://imrandev.bd/contact" class="underline hover:text-slate-600">Contact</a>
            </div>
        </div>
    </nav>
    <main>@yield('content')</main>
</body>
</html>
