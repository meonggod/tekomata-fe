<x-layouts.app :title="($product['name'] ?? '') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ $product['name'] ?? '' }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.products.back_to_products') }}
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

        @error('product')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        {{-- Product details --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ $product['name'] ?? '' }}</h2>
                <div class="flex items-center gap-2">
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
            <dl class="divide-y divide-gray-100">
                @if (!empty($product['sku']))
                    <div class="flex items-center px-5 py-3">
                        <dt class="w-40 shrink-0 text-sm text-gray-500">{{ __('messages.products.sku_label') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $product['sku'] }}</dd>
                    </div>
                @endif
                <div class="flex items-center px-5 py-3">
                    <dt class="w-40 shrink-0 text-sm text-gray-500">{{ __('messages.products.unit_label') }}</dt>
                    <dd class="flex items-center gap-2 text-sm font-medium text-gray-900">
                        {{ $product['unit'] }}
                        @if ($product['is_fractional'] ?? false)
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600">{{ __('messages.products.fractional_yes') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center px-5 py-3">
                    <dt class="w-40 shrink-0 text-sm text-gray-500">{{ __('messages.products.price_label') }}</dt>
                    <dd class="text-sm font-medium text-gray-900">
                        {{ $product['default_price'] }} {{ $product['currency_code'] }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Stock by warehouse --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.products.stock_section') }}</h2>
                <a href="{{ route('products.movements', $product['id']) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800">
                    {{ __('messages.products.movements_link') }}
                </a>
            </div>
            @forelse ($product['stock'] ?? [] as $entry)
                <div class="flex items-center border-b border-gray-100 px-5 py-3 last:border-b-0">
                    <span class="flex-1 text-sm text-gray-900">{{ $entry['warehouse_name'] }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $entry['balance'] }}</span>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-500">{{ __('messages.products.no_stock') }}</p>
            @endforelse
        </div>

        {{-- Adjust stock --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.products.adjust_title') }}</h2>
            </div>

            @error('adjustment')
                <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</div>
            @enderror

            @if (empty($warehouses))
                <p class="px-5 py-4 text-sm text-gray-500">{{ __('messages.products.no_warehouses') }}</p>
            @else
                <form method="POST" action="{{ route('products.stock', $product['id']) }}"
                      class="divide-y divide-gray-100">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
                        {{-- Warehouse --}}
                        <div>
                            <label for="warehouse_id" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.products.warehouse_label') }} <span class="text-red-500">*</span>
                            </label>
                            <select id="warehouse_id" name="warehouse_id" required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">—</option>
                                @foreach ($warehouses as $wh)
                                    @if ($wh['is_active'] ?? true)
                                        <option value="{{ $wh['id'] }}"
                                                {{ old('warehouse_id') === $wh['id'] ? 'selected' : '' }}>
                                            {{ $wh['name'] }}
                                            @if (!empty($wh['code'])) ({{ $wh['code'] }}) @endif
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Quantity delta --}}
                        <div>
                            <label for="quantity_delta" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.products.delta_label') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="quantity_delta" name="quantity_delta"
                                   value="{{ old('quantity_delta') }}" required
                                   placeholder="{{ ($product['is_fractional'] ?? false) ? '10.5' : '10' }}"
                                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-400">{{ __('messages.products.delta_hint') }}</p>
                            @error('quantity_delta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.products.reason_label') }} <span class="text-red-500">*</span>
                            </label>
                            <select id="reason" name="reason" required
                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">—</option>
                                <option value="manual_adjustment" {{ old('reason') === 'manual_adjustment' ? 'selected' : '' }}>
                                    {{ __('messages.products.reason_manual_adjustment') }}
                                </option>
                                <option value="import" {{ old('reason') === 'import' ? 'selected' : '' }}>
                                    {{ __('messages.products.reason_import') }}
                                </option>
                                <option value="correction" {{ old('reason') === 'correction' ? 'selected' : '' }}>
                                    {{ __('messages.products.reason_correction') }}
                                </option>
                            </select>
                            @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Note --}}
                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.products.note_label') }}
                            </label>
                            <input type="text" id="note" name="note" value="{{ old('note') }}"
                                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-400">{{ __('messages.products.note_hint') }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end px-5 py-4">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.products.apply_adjustment') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
