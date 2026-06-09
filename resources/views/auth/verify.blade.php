<x-layouts.public :title="__('messages.register.verify_failed_title') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-gray-900">tekomata</a>
            <x-lang-switcher />
        </div>
    </x-slot:header>

    {{-- Reached only when the token is missing, expired or already used. A valid
         token redirects to login, so this view is the failure path by design. --}}
    <div class="mx-auto max-w-sm py-16">
        <div class="rounded-lg border border-red-200 bg-red-50 p-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.register.verify_failed_title') }}</h1>
            <p class="mt-4 text-sm leading-relaxed text-gray-600">
                {{ __('messages.register.verify_failed_body') }}
            </p>
            <a href="{{ route('register') }}"
               class="mt-6 inline-flex rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                {{ __('messages.register.verify_failed_cta') }}
            </a>
        </div>
    </div>
</x-layouts.public>
