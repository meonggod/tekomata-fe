{{--
    Country picker. The baseline is a styled native <select> that works with no
    JS and is the submitted source of truth. app.js progressively enhances it
    into a searchable, flag-rich listbox (see enhanceCountrySelects); the native
    control is hidden but keeps submitting the chosen ISO 3166-1 alpha-2 code.
    Renders nothing when the catalog is empty/unreachable — the field is optional.
--}}
@props([
    'countries' => [],
    'name' => 'country_code',
    'selected' => null,
])

@if (! empty($countries))
    @php($hasError = $errors->has($name))

    <div data-country-select
         data-search-label="{{ __('messages.register.country_search') }}"
         data-empty-label="{{ __('messages.register.country_no_results') }}"
         class="relative mt-1.5">
        <select id="{{ $name }}" name="{{ $name }}" data-country-native
                class="block w-full rounded-lg border bg-white py-2.5 pl-3 pr-10 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
            <option value="" data-placeholder>{{ __('messages.register.country_placeholder') }}</option>
            @foreach ($countries as $country)
                @php($code = $country['country_code'] ?? '')
                <option value="{{ $code }}"
                        data-flag="{{ $country['flag_image'] ?? '' }}"
                        data-name="{{ $country['name'] ?? $code }}"
                        data-dial="{{ $country['dial_code'] ?? '' }}"
                        @selected((string) old($name, $selected) === (string) $code)>
                    {{ $country['name'] ?? $code }}@isset($country['dial_code']) ({{ $country['dial_code'] }})@endisset
                </option>
            @endforeach
        </select>
    </div>
@endif
