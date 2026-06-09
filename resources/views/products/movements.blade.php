<x-layouts.app :title="__('messages.products.movements_title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'),          'url' => route('dashboard')],
            ['label' => __('messages.nav.products'),           'url' => route('products.index')],
            ['label' => $product['name'] ?? '',                'url' => route('products.show', $product['id'])],
            ['label' => __('messages.products.movements_title')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-4xl space-y-4">
        <p class="text-sm text-gray-600">
            {{ $product['name'] ?? '' }}
            @if (!empty($product['sku'])) · {{ $product['sku'] }} @endif
        </p>

        {{-- Filters --}}
        <form method="GET" action="{{ route('products.movements', $product['id']) }}"
              class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-600">{{ __('messages.products.filter_warehouse') }}</label>
                <select name="warehouse_id"
                        class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">{{ __('messages.products.filter_warehouse') }}</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh['id'] }}"
                                {{ ($filters['warehouse_id'] ?? '') === $wh['id'] ? 'selected' : '' }}>
                            {{ $wh['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">{{ __('messages.products.filter_from') }}</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">{{ __('messages.products.filter_to') }}</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ __('messages.products.filter_apply') }}
            </button>
            @if (!empty(array_filter($filters)))
                <a href="{{ route('products.movements', $product['id']) }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50">
                    {{ __('messages.products.filter_reset') }}
                </a>
            @endif
        </form>

        {{-- Movement list --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @php
            $reasonLabels = [
                'import'            => __('messages.products.reason_import'),
                'manual_adjustment' => __('messages.products.reason_manual_adjustment'),
                'correction'        => __('messages.products.reason_correction'),
            ];
            @endphp

            @forelse ($movements as $m)
                @php $delta = $m['quantity_delta'] ?? '0'; @endphp
                <div class="flex flex-wrap items-start gap-x-6 gap-y-1 border-b border-gray-100 px-5 py-4 last:border-b-0">
                    <div class="w-36 shrink-0">
                        <p class="text-xs text-gray-400">
                            {{ \Carbon\Carbon::createFromTimestampMs($m['created_at'])->format('d M Y, H:i') }}
                        </p>
                        <p class="text-sm text-gray-700">{{ $m['warehouse_name'] ?? '' }}</p>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold {{ str_starts_with(ltrim($delta, '+'), '-') ? 'text-red-600' : 'text-green-700' }}">
                            {{ str_starts_with(ltrim($delta, '+'), '-') ? '' : '+' }}{{ $delta }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $reasonLabels[$m['reason'] ?? ''] ?? $m['reason'] ?? '' }}</p>
                        @if (!empty($m['note']))
                            <p class="mt-0.5 text-xs text-gray-400">{{ $m['note'] }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.products.no_movements') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
