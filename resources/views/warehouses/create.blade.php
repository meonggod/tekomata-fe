<x-layouts.app :title="__('messages.warehouses.create_title') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.warehouses.create_title') }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('warehouses.index') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.warehouses.back_to_products') }}
                </a>
                <x-lang-switcher />
            </div>
        </div>
    </x-slot:header>

    <div class="mx-auto w-full max-w-xl">
        @error('warehouse')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('warehouses.store') }}" class="divide-y divide-gray-100">
                @csrf

                <div class="space-y-4 px-5 py-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.warehouses.name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.warehouses.code_label') }}
                        </label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-400">{{ __('messages.warehouses.code_hint') }}</p>
                        @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-5 py-4">
                    <a href="{{ route('warehouses.index') }}"
                       class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        {{ __('messages.warehouses.cancel') }}
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.warehouses.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
