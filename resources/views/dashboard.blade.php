<x-layouts.app :title="__('messages.dashboard.title') . ' · tekomata'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.dashboard.title') }}</h1>
            <div class="flex items-center gap-4">
                <x-company-switcher :companies="$companies ?? []" :activeId="$activeCompanyId ?? null" />
                <x-lang-switcher />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-900">
                        {{ __('messages.dashboard.sign_out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot:header>

    @error('company_id')
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-gray-700">{{ __('messages.dashboard.placeholder') }}</p>
    </div>
</x-layouts.app>
