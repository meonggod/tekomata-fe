<x-layouts.app :title="($category['name'] ?? '') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ $category['name'] ?? '' }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('categories.index') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.categories.back_to_categories') }}
                </a>
                <x-lang-switcher />
            </div>
        </div>
    </x-slot:header>

    <div class="mx-auto w-full max-w-3xl space-y-6">

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @error('category')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        {{-- Category details --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">{{ $category['name'] ?? '' }}</h2>
                    @if (! empty($category['code']))
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $category['code'] }}</span>
                    @endif
                    @if (! ($category['is_active'] ?? true))
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-400">
                            {{ __('messages.categories.status_inactive') }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('categories.edit', $category['id']) }}"
                       class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        {{ __('messages.categories.edit') }}
                    </a>
                    <form method="POST" action="{{ route('categories.destroy', $category['id']) }}"
                          onsubmit="return confirm('{{ __('messages.categories.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            {{ __('messages.categories.delete') }}
                        </button>
                    </form>
                </div>
            </div>
            @if (! empty($category['description']))
                <p class="px-5 py-3 text-sm text-gray-600">{{ $category['description'] }}</p>
            @endif
        </div>

        {{-- Products in this category --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.categories.products_section') }}</h2>
            </div>

            @error('products')
                <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</div>
            @enderror

            @forelse ($categoryProducts as $product)
                <div class="flex items-center border-b border-gray-100 px-5 py-3 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <span class="font-medium text-gray-900">{{ $product['name'] }}</span>
                        @if (! empty($product['sku']))
                            <span class="ml-2 text-xs text-gray-400">{{ $product['sku'] }}</span>
                        @endif
                    </div>
                    <form method="POST"
                          action="{{ route('categories.products.remove', [$category['id'], $product['id']]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            {{ __('messages.categories.remove_product') }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-500">{{ __('messages.categories.no_products') }}</p>
            @endforelse
        </div>

        {{-- Add products --}}
        @if (! empty($availableProducts))
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.categories.add_products_title') }}</h2>
                </div>
                <form method="POST" action="{{ route('categories.products.add', $category['id']) }}"
                      class="divide-y divide-gray-100">
                    @csrf
                    <div class="px-5 py-4">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('messages.categories.product_ids_label') }}
                        </label>
                        <select name="product_ids[]" multiple required
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                size="{{ min(count($availableProducts), 6) }}">
                            @foreach ($availableProducts as $product)
                                <option value="{{ $product['id'] }}">
                                    {{ $product['name'] }}
                                    @if (! empty($product['sku'])) ({{ $product['sku'] }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end px-5 py-4">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.categories.add_products_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-layouts.app>
