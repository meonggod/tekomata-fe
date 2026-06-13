@props(['title' => 'tekomata internal'])

@php
    // The staff principal is resolved server-side from the staff session JWT.
    // Every page using this layout is behind `internal.auth`, so it is always set.
    $staff = app(\App\Services\Tekomata\StaffTokenStore::class);
    $staffEmail = $staff->email() ?: 'staff';
    $staffRole = $staff->role();

    // Console navigation. Every panel is readable by any staff (mutate controls
    // inside each page are role-gated), so the whole nav is shown to all staff.
    $inav = [
        ['internal.dashboard', __('messages.internal.nav.dashboard')],
        ['internal.billing.index', __('messages.internal.nav.billing')],
        ['internal.fx.index', __('messages.internal.nav.fx')],
        ['internal.regions.index', __('messages.internal.nav.regions')],
        ['internal.ai.index', __('messages.internal.nav.ai')],
        ['internal.cs.index', __('messages.internal.nav.cs')],
        ['internal.staff.index', __('messages.internal.nav.staff')],
    ];
@endphp

{{--
    Minimal standalone layout for the internal tekomata-staff console. Deliberately
    separate from x-layouts.app (no tenant sidebar / company switcher) — a different
    audience and a different identity (the staff principal, not a tenant user). Same
    Vite bundle, a slim dark top bar, and a centered column at the standard width.
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
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-6">
                <div class="flex items-baseline gap-2">
                    <span class="text-base font-bold tracking-tight text-white">tekomata</span>
                    <span class="rounded bg-indigo-500/20 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-300">internal</span>
                </div>
                <nav class="hidden items-center gap-1 lg:flex" aria-label="Internal">
                    @foreach ($inav as [$rn, $lbl])
                        <a href="{{ route($rn) }}"
                           class="rounded-md px-2.5 py-1.5 text-sm font-medium transition {{ request()->routeIs($rn) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white' }}">{{ $lbl }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden items-center gap-1.5 text-xs text-gray-400 sm:flex">
                    <span class="max-w-[14rem] truncate">{{ $staffEmail }}</span>
                    <span @class([
                        'rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                        'bg-indigo-500/20 text-indigo-300' => $staffRole === 'superadmin',
                        'bg-gray-600/40 text-gray-300' => $staffRole !== 'superadmin',
                    ])>{{ $staffRole }}</span>
                </span>
                <form method="POST" action="{{ route('internal.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-300 hover:text-white">{{ __('messages.nav.sign_out') }}</button>
                </form>
            </div>
        </div>

        {{-- Compact nav for small screens. --}}
        <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-4 py-1.5 lg:hidden" aria-label="Internal">
            @foreach ($inav as [$rn, $lbl])
                <a href="{{ route($rn) }}"
                   class="whitespace-nowrap rounded-md px-2.5 py-1.5 text-sm font-medium transition {{ request()->routeIs($rn) ? 'bg-white/10 text-white' : 'text-gray-300 hover:text-white' }}">{{ $lbl }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
