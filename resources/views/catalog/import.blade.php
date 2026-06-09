<x-layouts.app :title="__('messages.catalog.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.nav.catalog_import')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-5xl space-y-6">

        {{-- Import form --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.catalog.import_heading') }}</h2>
            </div>

            @error('catalog_file')
                <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $message }}
                </div>
            @enderror

            <form method="POST" action="{{ route('catalog.import.store') }}"
                  enctype="multipart/form-data" class="divide-y divide-gray-100">
                @csrf

                <div class="space-y-4 px-5 py-5">
                    <div>
                        <label for="catalog_file" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.catalog.import_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="file" id="catalog_file" name="catalog_file" accept=".csv,.txt" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600 space-y-1">
                        <p class="font-medium text-gray-700">{{ __('messages.catalog.format_heading') }}</p>
                        <p>{{ __('messages.catalog.format_columns') }}</p>
                        <p class="text-xs text-gray-500">{{ __('messages.catalog.format_warehouses_hint') }}</p>
                        <p class="text-xs text-gray-500">{{ __('messages.catalog.format_prices_hint') }}</p>
                        <a href="{{ asset('catalog-template.csv') }}" download
                           class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-500 mt-1">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ __('messages.catalog.download_template') }}
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-end px-5 py-4">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                        {{ __('messages.catalog.upload_button') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Import result (shown after a successful POST → redirect) --}}
        @if (session('import_result'))
            @php $result = session('import_result'); $summary = $result['summary'] ?? []; $importErrors = $result['errors'] ?? []; @endphp

            <div class="overflow-hidden rounded-xl border border-green-200 bg-white shadow-sm">
                <div class="border-b border-green-100 bg-green-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-green-900">{{ __('messages.catalog.result_heading') }}</h2>
                </div>
                <div class="px-5 py-4">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3 lg:grid-cols-4 text-sm">
                        @foreach ([
                            'rows_total'         => __('messages.catalog.result_rows_total'),
                            'rows_succeeded'     => __('messages.catalog.result_rows_succeeded'),
                            'rows_failed'        => __('messages.catalog.result_rows_failed'),
                            'products_created'   => __('messages.catalog.result_products_created'),
                            'products_updated'   => __('messages.catalog.result_products_updated'),
                            'warehouses_created' => __('messages.catalog.result_warehouses_created'),
                            'tiers_created'      => __('messages.catalog.result_tiers_created'),
                            'stock_movements'    => __('messages.catalog.result_stock_movements'),
                            'prices_upserted'    => __('messages.catalog.result_prices_upserted'),
                        ] as $key => $label)
                            @if (array_key_exists($key, $summary))
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                    <dd class="font-semibold text-gray-900">{{ $summary[$key] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>

                @if (!empty($importErrors))
                    <div class="border-t border-amber-100 bg-amber-50 px-5 py-4">
                        <p class="mb-3 text-sm font-medium text-amber-900">{{ __('messages.catalog.result_errors_heading') }}</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-amber-200 text-amber-700">
                                        <th class="pb-1.5 pr-4 font-medium">{{ __('messages.catalog.result_col_row') }}</th>
                                        <th class="pb-1.5 pr-4 font-medium">{{ __('messages.catalog.result_col_field') }}</th>
                                        <th class="pb-1.5 font-medium">{{ __('messages.catalog.result_col_message') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100">
                                    @foreach ($importErrors as $err)
                                        <tr>
                                            <td class="py-1.5 pr-4 text-amber-800">{{ $err['row'] ?? '—' }}</td>
                                            <td class="py-1.5 pr-4 font-mono text-amber-800">{{ $err['field'] ?? '—' }}</td>
                                            <td class="py-1.5 text-amber-700">{{ $err['message'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

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
