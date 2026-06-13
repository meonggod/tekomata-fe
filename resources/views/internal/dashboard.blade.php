<x-layouts.internal :title="'Internal · tekomata'">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.dashboard.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">
            {{ __('messages.internal.dashboard.signed_in_as', ['email' => $staffEmail]) }}
            <span @class([
                'ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                'bg-indigo-100 text-indigo-700' => $isSuperadmin,
                'bg-gray-100 text-gray-600' => ! $isSuperadmin,
            ])>{{ $role }}</span>
        </p>
    </div>

    @unless ($isSuperadmin)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('messages.internal.dashboard.ops_notice') }}
        </div>
    @endunless

    @php
        $cards = [
            ['internal.billing.index', __('messages.internal.nav.billing'), __('messages.internal.dashboard.card_billing')],
            ['internal.fx.index', __('messages.internal.nav.fx'), __('messages.internal.dashboard.card_fx')],
            ['internal.regions.index', __('messages.internal.nav.regions'), __('messages.internal.dashboard.card_regions')],
            ['internal.ai.index', __('messages.internal.nav.ai'), __('messages.internal.dashboard.card_ai')],
            ['internal.cs.index', __('messages.internal.nav.cs'), __('messages.internal.dashboard.card_cs')],
            ['internal.staff.index', __('messages.internal.nav.staff'), __('messages.internal.dashboard.card_staff')],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as [$rn, $title, $desc])
            <a href="{{ route($rn) }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow">
                <h2 class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700">{{ $title }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.internal>
