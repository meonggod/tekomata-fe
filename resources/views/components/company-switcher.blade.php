{{--
    Active-company switcher for multi-company users. Posts the chosen company to
    /companies/switch, which re-issues a token scoped to it. Renders nothing for
    users who belong to a single company (or none).
--}}
@props(['companies' => [], 'activeId' => null])

@if (is_array($companies) && count($companies) > 1)
    <form method="POST" action="{{ route('companies.switch') }}" class="flex items-center gap-2">
        @csrf
        <label for="company_id" class="sr-only">{{ __('messages.dashboard.company') }}</label>
        <select id="company_id" name="company_id"
                class="rounded-md border border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
            @foreach ($companies as $company)
                <option value="{{ $company['company_id'] ?? '' }}" @selected(($company['company_id'] ?? null) === $activeId)>
                    {{ $company['name'] ?? ($company['company_id'] ?? '') }}@isset($company['role']) · {{ $company['role'] }}@endisset
                </option>
            @endforeach
        </select>
        <button type="submit"
                class="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
            {{ __('messages.dashboard.switch_company') }}
        </button>
    </form>
@endif
