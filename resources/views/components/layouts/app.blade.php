@props(['title' => 'tekomata', 'fullHeight' => false])

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

    <x-sidebar />

    <div class="flex {{ $fullHeight ? 'h-full' : 'min-h-full' }} flex-col lg:ml-64">

        {{-- Mobile top bar --}}
        <div class="flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 lg:hidden">
            <button id="sidebar-open" type="button"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                    aria-label="{{ __('messages.nav.open_sidebar') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" class="text-sm font-bold tracking-tight text-gray-900">tekomata</a>
        </div>

        {{-- Breadcrumb bar --}}
        @isset($breadcrumbs)
            <div class="border-b border-gray-100 bg-white px-4 py-2.5 sm:px-6 lg:px-8">
                {{ $breadcrumbs }}
            </div>
        @endisset

        {{-- Page content --}}
        <main class="flex-1 {{ $fullHeight ? 'overflow-hidden' : 'px-4 py-6 sm:px-6 lg:px-8' }}">
            {{ $slot }}
        </main>
    </div>

    {{-- Global, dismissible "something went wrong" modal for unexpected backend
         failures (shown only when the api_error flash is present). --}}
    <x-error-modal />
</body>
</html>
