<x-layouts.app :title="__('messages.billing.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.billing.title')],
        ]" />
    </x-slot:breadcrumbs>

    @php
        $money = fn ($v) => __('messages.billing.money_prefix') . ' ' . number_format((float) $v, 0, ',', '.');

        $kindLabel = function (string $key) {
            $full = "messages.billing.kind.$key";
            $translated = __($full);
            return $translated === $full ? ucfirst($key) : $translated;
        };

        $when = function ($ts) {
            if (empty($ts)) {
                return '—';
            }
            try {
                return \Illuminate\Support\Carbon::parse($ts)->translatedFormat('d M Y, H:i');
            } catch (\Throwable) {
                return (string) $ts;
            }
        };

        $date = function ($ts) {
            if (empty($ts)) {
                return '—';
            }
            try {
                return \Illuminate\Support\Carbon::parse($ts)->translatedFormat('d M Y');
            } catch (\Throwable) {
                return (string) $ts;
            }
        };

        // Stable display order for the by-kind split; any extra keys the API
        // returns are appended so nothing is silently dropped.
        $kindOrder = ['usage', 'subscription', 'feature', 'ai'];
        $kinds = collect($kindOrder)->filter(fn ($k) => array_key_exists($k, $byKind))
            ->merge(collect(array_keys($byKind))->reject(fn ($k) => in_array($k, $kindOrder, true)))
            ->values();
        $totalVal = (float) $totalIdr;
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="text-sm text-gray-600">{{ __('messages.billing.subtitle') }}</p>

            {{-- Period selector --}}
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm">
                @foreach ($periods as $p)
                    <a href="{{ route('billing.index', ['period' => $p]) }}"
                       @class([
                           'rounded-md px-3 py-1.5 text-sm font-medium transition',
                           'bg-indigo-600 text-white shadow-sm' => $p === $period,
                           'text-gray-600 hover:bg-gray-100' => $p !== $period,
                       ])>{{ __('messages.billing.period_days', ['days' => $p]) }}</a>
                @endforeach
            </div>
        </div>

        {{-- ===== Totals ===== --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-medium text-gray-500">{{ __('messages.billing.total_label') }}</h2>
                <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $money($totalIdr) }}</p>
                <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.billing.period_range', ['from' => $date($from), 'to' => $date($to)]) }}</p>
            </section>

            @if ($spendable !== null)
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-medium text-gray-500">{{ __('messages.billing.spendable_label') }}</h2>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $money($spendable) }}</p>
                    <p class="mt-1.5 text-xs text-gray-500">
                        {{ __('messages.billing.spendable_hint') }}
                        <a href="{{ route('wallet.index') }}" class="font-medium text-indigo-600 hover:underline">{{ __('messages.billing.spendable_link') }}</a>
                    </p>
                </section>
            @endif
        </div>

        {{-- ===== Breakdown by kind ===== --}}
        @if ($kinds->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">{{ __('messages.billing.by_kind_title') }}</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($kinds as $kind)
                        @php
                            $amount = (float) ($byKind[$kind] ?? 0);
                            $pct = $totalVal > 0 ? max(2, round($amount / $totalVal * 100)) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $kindLabel($kind) }}</span>
                                <span class="text-gray-900">{{ $money($byKind[$kind] ?? 0) }}</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ===== Charges (line-itemed) ===== --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">{{ __('messages.billing.charges_title') }}</h2>
            </div>

            @if (empty($charges))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.billing.charges_empty') }}</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($charges as $charge)
                        @php $items = $charge['line_items'] ?? []; @endphp
                        <li>
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ $charge['source_reference'] ?? ($charge['id'] ?? '—') }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $when($charge['created_at'] ?? null) }}
                                            @unless ($charge['incurred'] ?? true)
                                                · <span class="text-amber-600">{{ __('messages.billing.pre_incurred') }}</span>
                                            @endunless
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900">{{ $money($charge['total_idr'] ?? 0) }}</span>
                                        <svg class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </div>
                                </summary>

                                @if (! empty($items))
                                    <div class="overflow-x-auto border-t border-gray-100 bg-gray-50/50">
                                        <table class="min-w-full text-sm">
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($items as $item)
                                                    @php
                                                        $origCur = $item['original_currency'] ?? null;
                                                        $showOrig = $origCur && strtoupper($origCur) !== 'IDR';
                                                    @endphp
                                                    <tr>
                                                        <td class="px-5 py-2.5">
                                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $kindLabel($item['kind'] ?? '—') }}</span>
                                                        </td>
                                                        <td class="px-5 py-2.5 text-gray-700">
                                                            {{ $item['description'] ?? ($item['feature_key'] ?? '—') }}
                                                            @if (! empty($item['quantity']) && (float) $item['quantity'] != 1)
                                                                <span class="text-xs text-gray-400">× {{ rtrim(rtrim(number_format((float) $item['quantity'], 4, '.', ''), '0'), '.') }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="whitespace-nowrap px-5 py-2.5 text-right text-xs text-gray-400">
                                                            @if ($showOrig)
                                                                {{ strtoupper($origCur) }} {{ number_format((float) ($item['original_amount'] ?? 0), 2, ',', '.') }}
                                                            @endif
                                                        </td>
                                                        <td class="whitespace-nowrap px-5 py-2.5 text-right font-medium text-gray-900">{{ $money($item['amount_idr'] ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </details>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
