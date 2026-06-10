<x-layouts.app :title="__('messages.catalog.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.nav.catalog_import')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="space-y-6">
        <x-products-tabs active="catalog" />

        {{-- Importing now lives on the product list page (async upload + live
             tracker + review + history). This page is the read-only catalog browse. --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-indigo-900">{{ __('messages.catalog.import.panel_title') }}</h2>
                <p class="mt-0.5 text-xs text-indigo-700">{{ __('messages.catalog.import.panel_subtitle') }}</p>
            </div>
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                {{ __('messages.catalog.import.button') }}
            </a>
        </div>

        {{-- Catalog browse --}}
        <div>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.catalog.browse_heading') }}</h2>
            </div>

            <form method="GET" action="{{ route('catalog.import') }}" class="mt-3 flex gap-2">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="{{ __('messages.catalog.search_placeholder') }}"
                       class="block flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <button type="submit"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('messages.catalog.filter_apply') }}
                </button>
                @if ($search)
                    <a href="{{ route('catalog.import') }}"
                       class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50">
                        {{ __('messages.catalog.filter_reset') }}
                    </a>
                @endif
            </form>

            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                @forelse ($products as $product)
                    <div class="border-b border-gray-100 px-5 py-4 last:border-b-0">
                        <div class="flex flex-wrap items-start gap-4">
                            {{-- Name + SKU + unit --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ $product['name'] }}</span>
                                    @if (!empty($product['sku']))
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $product['sku'] }}</span>
                                    @endif
                                    @if ($product['is_fractional'] ?? false)
                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600">{{ __('messages.products.fractional_yes') }}</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $product['unit'] }} · {{ $product['default_price'] }} {{ $product['currency_code'] }}</p>
                            </div>

                            {{-- Per-warehouse stock --}}
                            <div class="shrink-0 min-w-0 w-full sm:w-auto sm:max-w-xs">
                                @if (!empty($product['stock']))
                                    <p class="text-xs font-medium text-gray-500 mb-1">{{ __('messages.catalog.col_stock') }}</p>
                                    <ul class="space-y-0.5">
                                        @foreach ($product['stock'] as $stock)
                                            <li class="text-xs text-gray-700">
                                                <span class="font-medium">{{ $stock['warehouse_name'] }}</span>
                                                <span class="text-gray-400 mx-1">·</span>{{ $stock['balance'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-xs text-gray-400">{{ __('messages.catalog.no_stock') }}</p>
                                @endif
                            </div>

                            {{-- Per-tier prices --}}
                            <div class="shrink-0 min-w-0 w-full sm:w-auto sm:max-w-xs">
                                @if (!empty($product['prices']))
                                    <p class="text-xs font-medium text-gray-500 mb-1">{{ __('messages.catalog.col_prices') }}</p>
                                    <ul class="space-y-0.5">
                                        @foreach ($product['prices'] as $price)
                                            <li class="text-xs text-gray-700">
                                                <span class="font-medium">{{ $price['tier_name'] }}</span>
                                                <span class="text-gray-400 mx-1">·</span>{{ $price['price'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-xs text-gray-400">{{ __('messages.catalog.no_prices') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.catalog.no_products') }}</p>
                @endforelse
            </div>
        </div>

    </div>
</x-layouts.app>
