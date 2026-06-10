@props(['title' => 'tekomata internal'])

{{--
    Minimal standalone layout for the internal tekomata-staff area. Deliberately
    separate from x-layouts.app (no tenant sidebar / company switcher) — this is
    a different audience. Same Vite bundle, a slim top bar, and a centered column
    at the standard page width.
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
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    <header class="border-b border-gray-200 bg-gray-900">
        <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-baseline gap-2">
                <span class="text-base font-bold tracking-tight text-white">tekomata</span>
                <span class="rounded bg-indigo-500/20 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-300">internal</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-300 hover:text-white">{{ __('messages.nav.sign_out') }}</button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>
</body>
</html>
