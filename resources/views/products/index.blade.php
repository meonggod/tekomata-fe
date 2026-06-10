<x-layouts.app :title="__('messages.products.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.nav.products')],
        ]" />
    </x-slot:breadcrumbs>

    <div>
        <x-products-tabs active="products" />

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">{{ __('messages.products.subtitle') }}</p>
            <div class="flex items-center gap-2">
                <button type="button" data-import-open
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    {{ __('messages.catalog.import.button') }}
                </button>
                <a href="{{ route('products.create') }}"
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                    {{ __('messages.products.add_product') }}
                </a>
            </div>
        </div>

        @include('products.partials.import')

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @error('product')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <form method="GET" action="{{ route('products.index') }}" class="mt-4 flex gap-2">
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="{{ __('messages.products.search_placeholder') }}"
                   class="block flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('messages.products.filter_apply') }}
            </button>
            @if ($search)
                <a href="{{ route('products.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50">
                    {{ __('messages.products.filter_reset') }}
                </a>
            @endif
        </form>

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @forelse ($products as $product)
                <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $product['name'] }}</span>
                            @if (!empty($product['sku']))
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $product['sku'] }}</span>
                            @endif
                            @if ($product['is_fractional'] ?? false)
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600">{{ __('messages.products.fractional_yes') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">{{ $product['unit'] }}</p>
                        <p class="text-sm font-medium text-gray-700">
                            {{ $product['default_price'] }} {{ $product['currency_code'] }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('products.show', $product['id']) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            {{ __('messages.products.view') }}
                        </a>
                        <a href="{{ route('products.edit', $product['id']) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            {{ __('messages.products.edit') }}
                        </a>
                        <form method="POST" action="{{ route('products.destroy', $product['id']) }}"
                              onsubmit="return confirm('{{ __('messages.products.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                {{ __('messages.products.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.products.no_products') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
