<x-layouts.internal :title="'FX rates · tekomata internal'">
    @php
        $when = function ($ms) {
            if (empty($ms)) {
                return '—';
            }
            try {
                return \Illuminate\Support\Carbon::createFromTimestampMs((int) $ms)->translatedFormat('d M Y, H:i:s');
            } catch (\Throwable) {
                return (string) $ms;
            }
        };

        // Most recent fetch across all pairs — the "last synced" headline.
        $lastSynced = collect($rates)->pluck('fetched_at')->filter()->max();
        $anyStale = collect($rates)->contains(fn ($r) => (bool) ($r['stale'] ?? false));
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.fx.title') }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.fx.subtitle') }}</p>
        </div>

        @if ($configured)
            <form method="POST" action="{{ route('internal.fx.sync') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M3.985 19.644V14.65h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.181m0-4.992v4.992" />
                    </svg>
                    {{ __('messages.internal.fx.sync_now') }}
                </button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @error('fx_sync')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @unless ($configured)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('messages.internal.fx.not_configured') }}
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-600">
            <span>{{ __('messages.internal.fx.last_synced') }}: <span class="font-medium text-gray-900">{{ $when($lastSynced) }}</span></span>
            @if ($anyStale)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">
                    {{ __('messages.internal.fx.has_stale') }}
                </span>
            @endif
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @if (empty($rates))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.internal.fx.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-3">{{ __('messages.internal.fx.col_pair') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('messages.internal.fx.col_rate') }}</th>
                                <th class="px-5 py-3">{{ __('messages.internal.fx.col_source') }}</th>
                                <th class="px-5 py-3">{{ __('messages.internal.fx.col_fetched') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('messages.internal.fx.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($rates as $r)
                                @php $stale = (bool) ($r['stale'] ?? false); @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium text-gray-900">
                                        {{ $r['base_code'] ?? '—' }} → {{ $r['quote_code'] ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-gray-900">{{ $r['rate'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $r['source'] ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $when($r['fetched_at'] ?? null) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-red-50 text-red-700' => $stale,
                                            'bg-emerald-50 text-emerald-700' => ! $stale,
                                        ])>{{ $stale ? __('messages.internal.fx.stale') : __('messages.internal.fx.fresh') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endunless
</x-layouts.internal>
