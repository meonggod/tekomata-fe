<x-layouts.app :title="__('messages.products.edit_title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'),         'url' => route('dashboard')],
            ['label' => __('messages.nav.products'),          'url' => route('products.index')],
            ['label' => $product['name'] ?? '',               'url' => route('products.show', $product['id'])],
            ['label' => __('messages.products.edit_title')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-xl space-y-6">
        @error('product')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('products.update', $product['id']) }}" class="divide-y divide-gray-100">
                @csrf
                @method('PUT')

                <div class="space-y-4 px-5 py-5">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.products.name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $product['name'] ?? '') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- SKU --}}
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.products.sku_label') }}
                        </label>
                        <input type="text" id="sku" name="sku"
                               value="{{ old('sku', $product['sku'] ?? '') }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.products.sku_hint') }}</p>
                        @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Unit --}}
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.products.unit_label') }} <span class="text-red-500">*</span>
                        </label>
                        @php
                            $unitOptions = ['piece','box','kg','liter','meter','pack','dozen','carton','bag','can','bottle'];
                            $currentUnit = old('unit', $product['unit'] ?? '');
                            if ($currentUnit !== '' && ! in_array($currentUnit, $unitOptions, true)) {
                                $unitOptions[] = $currentUnit;
                            }
                        @endphp
                        <select id="unit" name="unit" required
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">—</option>
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" {{ $currentUnit === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.products.unit_hint') }}</p>
                        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fractional --}}
                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="is_fractional" name="is_fractional" value="1"
                               {{ old('is_fractional', $product['is_fractional'] ?? false) ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <label for="is_fractional" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.products.fractional_label') }}
                            </label>
                            <p class="text-xs text-gray-400">{{ __('messages.products.fractional_hint') }}</p>
                        </div>
                    </div>

                    {{-- Default price --}}
                    <div>
                        <label for="default_price" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.products.price_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="default_price" name="default_price"
                               value="{{ old('default_price', $product['default_price'] ?? '') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('default_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label for="currency_code" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.products.currency_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="currency_code" name="currency_code" required
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">—</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency['code'] }}"
                                        {{ old('currency_code', $product['currency_code'] ?? '') === $currency['code'] ? 'selected' : '' }}>
                                    {{ $currency['code'] }} — {{ $currency['name'] ?? $currency['code'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('currency_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-5 py-4">
                    <a href="{{ route('products.show', $product['id']) }}"
                       class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        {{ __('messages.products.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.products.save') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Category assignment --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.products.categories_label') }}</h2>
            </div>

            @error('categories')
                <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</div>
            @enderror

            @if (empty($allCategories))
                <p class="px-5 py-4 text-sm text-gray-500">{{ __('messages.products.no_categories_hint') }}</p>
            @else
                <form method="POST" action="{{ route('products.categories', $product['id']) }}"
                      class="divide-y divide-gray-100">
                    @csrf
                    @method('PUT')
                    <div class="px-5 py-4">
                        <p class="mb-2 text-xs text-gray-500">{{ __('messages.products.categories_hint') }}</p>
                        <select name="category_ids[]" multiple
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                size="{{ min(count($allCategories), 6) }}">
                            @foreach ($allCategories as $cat)
                                <option value="{{ $cat['id'] }}"
                                        {{ in_array($cat['id'], $currentCategoryIds, true) ? 'selected' : '' }}>
                                    {{ $cat['name'] }}
                                    @if (!empty($cat['code'])) ({{ $cat['code'] }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end px-5 py-4">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.products.update_categories') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Photos & videos --}}
        @include('products.partials.media-manager')
    </div>
</x-layouts.app>
