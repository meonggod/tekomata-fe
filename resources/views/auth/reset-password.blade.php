<x-layouts.app :title="__('messages.reset.title') . ' · tekomata'">
    <x-slot:header>
        <x-site-header cta="sign_in" />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-md flex-col py-10 sm:py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ __('messages.reset.title') }}</h1>
            <p class="mt-1.5 text-sm text-gray-600">{{ __('messages.reset.subtitle') }}</p>

            @error('token')
                {{-- The link itself is invalid, used or expired — point the user
                     back to request a fresh one. --}}
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                    <a href="{{ route('password.request') }}" class="mt-1 inline-block font-semibold text-red-800 underline hover:text-red-900">
                        {{ __('messages.reset.request_new') }}
                    </a>
                </div>
            @enderror

            <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ old('token', $token) }}">

                {{-- New password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.reset.password_label') }}
                    </label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </span>
                        <input id="password" name="password" type="password" required autofocus
                               autocomplete="new-password" placeholder="••••••••"
                               class="block w-full rounded-lg border bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.reset.password_hint') }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="group flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                    {{ __('messages.reset.submit') }}
                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-gray-600">
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                {{ __('messages.reset.back_to_sign_in') }}
            </a>
        </p>
    </div>

    <x-site-footer />
</x-layouts.app>
