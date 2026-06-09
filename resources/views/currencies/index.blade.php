<x-layouts.app :title="__('messages.currencies.title') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.currencies.title') }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.currencies.back_to_dashboard') }}
                </a>
                <x-lang-switcher />
            </div>
        </div>
    </x-slot:header>

    <div class="mx-auto w-full max-w-3xl">
        <p class="text-sm text-gray-600">{{ __('messages.currencies.subtitle') }}</p>

        {{-- Success of an enable / disable / set-default action. --}}
        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Localised, code-derived failure (e.g. can't disable the default). --}}
        @error('currency')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.currencies.heading') }}</h2>
            </div>

            @forelse ($catalog as $currency)
                @php($code = $currency['code'] ?? '')
                @php($enabledRow = $enabledByCode[$code] ?? null)
                @php($isEnabled = $enabledRow !== null)
                @php($isDefault = (bool) ($enabledRow['is_default'] ?? false))

                <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0 {{ $isEnabled ? '' : 'bg-gray-50/50' }}">
                    {{-- Symbol glyph --}}
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-base font-semibold text-indigo-700">
                        {{ $currency['symbol'] ?? ($currency['symbol_native'] ?? $code) }}
                    </span>

                    {{-- Code + name + minor units --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $code }}</span>
                            @if ($isDefault)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ __('messages.currencies.default_badge') }}</span>
                            @endif
                        </div>
                        <p class="truncate text-sm text-gray-600">{{ $currency['name'] ?? '' }}</p>
                        @isset($currency['decimal_places'])
                            <p class="text-xs text-gray-400">{{ __('messages.currencies.decimals', ['count' => $currency['decimal_places']]) }}</p>
                        @endisset
                    </div>

                    {{-- Actions --}}
                    <div class="flex shrink-0 items-center gap-2">
                        @if (! $isEnabled)
                            <form method="POST" action="{{ route('currencies.enable') }}">
                                @csrf
                                <input type="hidden" name="currency_code" value="{{ $code }}">
                                <button type="submit"
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                    {{ __('messages.currencies.enable') }}
                                </button>
                            </form>
                        @else
                            @if (! $isDefault)
                                <form method="POST" action="{{ route('currencies.default', ['code' => $code]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                        {{ __('messages.currencies.make_default') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('currencies.disable', ['code' => $code]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">
                                        {{ __('messages.currencies.disable') }}
                                    </button>
                                </form>
                            @else
                                {{-- The default can't be disabled — set another default first. --}}
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-400" title="{{ __('messages.currencies.default_locked_hint') }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6.75a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25v-6.75a2.25 2.25 0 0 1 2.25-2.25Z" />
                                    </svg>
                                    {{ __('messages.currencies.enabled_label') }}
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.currencies.empty') }}</p>
            @endforelse
        </div>

        @if (empty($enabledByCode) && ! empty($catalog))
            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ __('messages.currencies.none_enabled') }}
            </p>
        @endif

        {{-- The product-in-use guard depends on a product table that doesn't
             exist yet (deferred to the CRUD-product story); only the default is
             protected for now. --}}
        <p class="mt-4 text-xs text-gray-400">{{ __('messages.currencies.in_use_note') }}</p>
    </div>
</x-layouts.app>
