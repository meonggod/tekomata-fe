<x-layouts.app :title="__('messages.dashboard.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard')],
        ]" />
    </x-slot:breadcrumbs>

    @error('company_id')
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror

    @if (!empty($companies) && count($companies) > 1)
        <div class="mb-6">
            <x-company-switcher :companies="$companies" :activeId="$activeCompanyId" />
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-gray-700">{{ __('messages.dashboard.placeholder') }}</p>
    </div>
</x-layouts.app>
