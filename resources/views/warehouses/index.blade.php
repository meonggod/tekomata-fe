<x-layouts.app :title="__('messages.warehouses.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.nav.warehouses')],
        ]" />
    </x-slot:breadcrumbs>

    <div class="mx-auto w-full max-w-3xl">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">{{ __('messages.warehouses.subtitle') }}</p>
            <a href="{{ route('warehouses.create') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ __('messages.warehouses.add_warehouse') }}
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @error('warehouse')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @forelse ($warehouses as $warehouse)
                <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $warehouse['name'] }}</span>
                            @if (!empty($warehouse['code']))
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $warehouse['code'] }}</span>
                            @endif
                            @if (!($warehouse['is_active'] ?? true))
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-400">
                                    {{ __('messages.warehouses.status_inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('warehouses.edit', $warehouse['id']) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            {{ __('messages.warehouses.edit') }}
                        </a>
                        <form method="POST" action="{{ route('warehouses.destroy', $warehouse['id']) }}"
                              onsubmit="return confirm('{{ __('messages.warehouses.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                {{ __('messages.warehouses.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.warehouses.no_warehouses') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
