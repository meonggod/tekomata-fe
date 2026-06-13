@props(['title' => 'tekomata internal'])

{{--
    Minimal centered layout for the staff-auth screens (login / forgot / set
    password). No console nav (the visitor isn't signed in yet) and no marketing
    chrome — a different audience from the tenant auth pages.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full flex-col bg-gray-900 text-gray-900 antialiased">
    <div class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 flex items-baseline justify-center gap-2">
                <span class="text-lg font-bold tracking-tight text-white">tekomata</span>
                <span class="rounded bg-indigo-500/20 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-300">internal</span>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
