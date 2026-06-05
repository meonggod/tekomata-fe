<x-layouts.app :title="__('messages.auth.sign_in_title') . ' · tekomata'">
    <x-slot:header>
        <x-site-header cta="get_started" />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-md flex-col py-10 sm:py-16">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ __('messages.auth.sign_in_title') }}</h1>
            <p class="mt-1.5 text-sm text-gray-600">{{ __('messages.auth.sign_in_subtitle') }}</p>

            @if (session('status'))
                {{-- One-off flash, e.g. "email verified — please sign in". --}}
                <p class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </p>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5" novalidate>
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.auth.email_label') }}
                    </label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input id="email" name="email" type="email" required autofocus
                               value="{{ old('email') }}" autocomplete="email" placeholder="you@company.com"
                               class="block w-full rounded-lg border bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        {{ __('messages.auth.password_label') }}
                    </label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </span>
                        <input id="password" name="password" type="password" required
                               autocomplete="current-password" placeholder="••••••••"
                               class="block w-full rounded-lg border bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <label for="remember_me" class="flex items-center gap-2 text-sm text-gray-600">
                    <input id="remember_me" name="remember_me" type="checkbox" value="1" @checked(old('remember_me'))
                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __('messages.auth.remember_me') }}
                </label>

                <button type="submit"
                        class="group flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                    {{ __('messages.auth.submit') }}
                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-gray-600">
            {{ __('messages.auth.no_account') }}
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                {{ __('messages.nav.get_started') }}
            </a>
        </p>
    </div>

    <x-site-footer />
</x-layouts.app>
