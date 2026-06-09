<x-layouts.public :title="__('messages.register.title') . ' · tekomata'">
    <x-slot:header>
        <x-site-header cta="sign_in" />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-md flex-col py-10 sm:py-16">
        @if (session('registered'))
            {{-- Generic success state. Shown for every accepted signup — it never
                 reveals whether the email was new, pending or already registered. --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </span>

                <h1 class="mt-5 text-xl font-semibold text-gray-900">{{ __('messages.register.check_email_title') }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    {!! __('messages.register.check_email_body', ['email' => '<span class="font-medium text-gray-900">' . e(session('email', '')) . '</span>']) !!}
                </p>

                <a href="{{ route('login') }}"
                   class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                    {{ __('messages.nav.sign_in') }}
                </a>
                <a href="{{ route('register') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    {{ __('messages.register.different_email') }}
                </a>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ __('messages.register.title') }}</h1>
                <p class="mt-1.5 text-sm text-gray-600">{{ __('messages.register.subtitle') }}</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.register.email_label') }}
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
                            <p class="mt-1.5 flex items-center gap-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.register.password_label') }}
                        </label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <input id="password" name="password" type="password" required
                                   autocomplete="new-password" placeholder="••••••••"
                                   class="block w-full rounded-lg border bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.register.password_hint') }}</p>
                        @enderror
                    </div>

                    {{-- Country (optional). Sets the entity's country_code, which
                         derives the default currency + WhatsApp dial code. Only
                         shown when the public catalog returned countries. --}}
                    @if (! empty($countries))
                        <div>
                            <label for="country_code" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                {{ __('messages.register.country_label') }}
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-500">{{ __('messages.register.country_optional') }}</span>
                            </label>
                            <x-country-select :countries="$countries" name="country_code" />
                            @error('country_code')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.register.country_hint') }}</p>
                            @enderror
                        </div>
                    @endif

                    <button type="submit"
                            class="group flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.register.submit') }}
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                <p class="mt-6 flex items-center justify-center gap-1.5 text-xs text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    {{ __('messages.register.secure_note') }}
                </p>
            </div>

            <p class="mt-6 text-center text-sm text-gray-600">
                {{ __('messages.register.have_account') }}
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    {{ __('messages.nav.sign_in') }}
                </a>
            </p>
        @endif
    </div>

    <x-site-footer />
</x-layouts.public>
