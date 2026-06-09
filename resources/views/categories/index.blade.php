<x-layouts.app :title="__('messages.categories.title') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.categories.title') }}</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.categories.back_to_products') }}
                </a>
                <x-lang-switcher />
            </div>
        </div>
    </x-slot:header>

    <div class="mx-auto w-full max-w-3xl">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">{{ __('messages.categories.subtitle') }}</p>
            <a href="{{ route('categories.create') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ __('messages.categories.add_category') }}
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @error('category')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @forelse ($categories as $category)
                <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0 {{ ($category['is_active'] ?? true) ? '' : 'bg-gray-50/50' }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $category['name'] }}</span>
                            @if (! empty($category['code']))
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $category['code'] }}</span>
                            @endif
                            @if (! ($category['is_active'] ?? true))
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-400">
                                    {{ __('messages.categories.status_inactive') }}
                                </span>
                            @endif
                        </div>
                        @if (! empty($category['description']))
                            <p class="truncate text-sm text-gray-500">{{ $category['description'] }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('categories.show', $category['id']) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            {{ __('messages.categories.view') }}
                        </a>
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
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('messages.categories.no_categories') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
