<x-layouts.internal :title="'Regions · tekomata internal'">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.regions.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.regions.subtitle') }}</p>
    </div>

    @error('region')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @unless ($isSuperadmin)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ __('messages.internal.read_only_notice') }}</div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-2">
        @php
            $sections = [
                ['countries', $countries, 'internal.regions.countries.toggle'],
                ['currencies', $currencies, 'internal.regions.currencies.toggle'],
            ];
        @endphp

        @foreach ($sections as [$kind, $rows, $routeName])
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.regions.'.$kind) }}</h2>
                </header>

                @if (empty($rows))
                    <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.regions.empty') }}</p>
                @else
                    <ul class="max-h-96 divide-y divide-gray-50 overflow-y-auto">
                        @foreach ($rows as $r)
                            @php $active = (bool) ($r['is_active'] ?? false); $code = $r['code'] ?? ''; @endphp
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <span class="font-mono text-xs font-semibold text-gray-500">{{ $code }}</span>
                                    <span class="ml-2 text-sm text-gray-900">{{ $r['name'] ?? '—' }}</span>
                                </div>
                                @if ($isSuperadmin)
                                    <form method="POST" action="{{ route($routeName, $code) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="active" value="{{ $active ? '0' : '1' }}">
                                        <button type="submit" @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-medium transition',
                                            'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $active,
                                            'bg-gray-100 text-gray-500 hover:bg-gray-200' => ! $active,
                                        ])>{{ $active ? __('messages.internal.actions.active') : __('messages.internal.actions.inactive') }}</button>
                                    </form>
                                @else
                                    <x-internal.active-badge :active="$active" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.internal>
