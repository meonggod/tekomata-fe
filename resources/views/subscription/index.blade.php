<x-layouts.app :title="__('messages.subscription.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.subscription.title')],
        ]" />
    </x-slot:breadcrumbs>

    @php
        $money = fn ($v) => __('messages.subscription.money_prefix') . ' ' . number_format((float) $v, 0, ',', '.');

        // Per-query base rate can be a sub-rupiah decimal — keep up to 4 places,
        // trimming trailing zeros, so e.g. "12.5000" reads as "Rp 12,5".
        $rate = function ($v) use ($money) {
            $n = (float) $v;
            $s = rtrim(rtrim(number_format($n, 4, ',', '.'), '0'), ',');
            return __('messages.subscription.money_prefix') . ' ' . $s;
        };

        $when = function ($ts) {
            if (empty($ts)) {
                return '—';
            }
            try {
                return \Illuminate\Support\Carbon::parse($ts)->translatedFormat('d M Y');
            } catch (\Throwable) {
                return (string) $ts;
            }
        };

        // The plan the company is currently on, resolved from the subscription's
        // plan_id against the catalogue (so we can show its name + figures).
        $currentPlanId = $subscription['plan_id'] ?? null;
        $autoRenew = (bool) ($subscription['auto_renew'] ?? false);
        $periodEnd = $subscription['current_period_end'] ?? null;
        $spendableKnown = $spendable !== null;
        $spendableVal = (float) $spendable;
    @endphp

    <div class="space-y-6">
        <p class="text-sm text-gray-600">{{ __('messages.subscription.subtitle') }}</p>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @error('subscription_action')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $message }}
                <a href="{{ route('wallet.index') }}" class="ml-1 font-medium text-indigo-600 hover:underline">
                    {{ __('messages.subscription.topup_link') }}
                </a>
            </div>
        @enderror

        {{-- ===== Current plan ===== --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-medium text-gray-500">{{ __('messages.subscription.current.label') }}</h2>
                    @if ($active && $currentPlanId)
                        <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                            {{ collect($plans)->firstWhere('id', $currentPlanId)['name'] ?? __('messages.subscription.current.paid_plan') }}
                        </p>
                        <p class="mt-1.5 text-xs text-gray-500">
                            @if ($autoRenew)
                                {{ __('messages.subscription.current.renews_on', ['date' => $when($periodEnd)]) }}
                            @else
                                {{ __('messages.subscription.current.lapses_on', ['date' => $when($periodEnd)]) }}
                            @endif
                        </p>
                    @else
                        <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ __('messages.subscription.current.free') }}</p>
                        <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.subscription.current.free_hint') }}</p>
                    @endif

                    @if ($baseRate !== null)
                        <p class="mt-2 text-xs text-gray-500">
                            {{ __('messages.subscription.current.base_rate', ['rate' => $rate($baseRate)]) }}
                        </p>
                    @endif
                </div>

                <div class="text-right">
                    @if ($spendableKnown)
                        <p class="text-xs text-gray-500">{{ __('messages.subscription.spendable_label') }}</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $money($spendable) }}</p>
                    @endif
                </div>
            </div>

            @if ($active && $autoRenew)
                <form method="POST" action="{{ route('subscription.cancel') }}" class="mt-4"
                      onsubmit="return confirm('{{ __('messages.subscription.cancel_confirm') }}');">
                    @csrf
                    <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        {{ __('messages.subscription.cancel_action') }}
                    </button>
                </form>
            @elseif ($active && ! $autoRenew)
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    {{ __('messages.subscription.cancelled_notice') }}
                </p>
            @endif
        </section>

        {{-- ===== Available plans ===== --}}
        <section class="space-y-3">
            <h2 class="font-semibold text-gray-900">{{ __('messages.subscription.plans_title') }}</h2>

            @if (empty($plans))
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
                    {{ __('messages.subscription.plans_empty') }}
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        @php
                            $isCurrent = $active && ($plan['id'] ?? null) === $currentPlanId;
                            $price = (float) ($plan['monthly_price'] ?? 0);
                            $affordable = ! $spendableKnown || $spendableVal >= $price;
                        @endphp
                        <div @class([
                            'flex flex-col rounded-xl border bg-white p-5 shadow-sm',
                            'border-indigo-500 ring-1 ring-indigo-500' => $isCurrent,
                            'border-gray-200' => ! $isCurrent,
                        ])>
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">{{ $plan['name'] ?? '—' }}</h3>
                                @if ($isCurrent)
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                        {{ __('messages.subscription.current_badge') }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
                                {{ $money($plan['monthly_price'] ?? 0) }}
                                <span class="text-sm font-normal text-gray-500">{{ __('messages.subscription.per_month') }}</span>
                            </p>

                            <dl class="mt-4 space-y-1.5 text-sm text-gray-600">
                                <div class="flex justify-between gap-2">
                                    <dt>{{ __('messages.subscription.base_rate_label') }}</dt>
                                    <dd class="font-medium text-gray-900">{{ $rate($plan['per_query_rate'] ?? 0) }}</dd>
                                </div>
                                @if (isset($plan['referral_percentage']))
                                    <div class="flex justify-between gap-2">
                                        <dt>{{ __('messages.subscription.referral_label') }}</dt>
                                        <dd class="font-medium text-gray-900">{{ rtrim(rtrim(number_format((float) $plan['referral_percentage'], 2, '.', ''), '0'), '.') }}%</dd>
                                    </div>
                                @endif
                            </dl>

                            <div class="mt-5 flex-1"></div>

                            @if ($isCurrent)
                                <button type="button" disabled
                                        class="w-full cursor-default rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400">
                                    {{ __('messages.subscription.current_button') }}
                                </button>
                            @elseif (! $affordable)
                                <a href="{{ route('wallet.index') }}"
                                   class="block w-full rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-center text-sm font-medium text-amber-800 transition hover:bg-amber-100">
                                    {{ __('messages.subscription.topup_to_subscribe') }}
                                </a>
                            @else
                                <form method="POST" action="{{ route('subscription.subscribe') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan['id'] ?? '' }}">
                                    <button type="submit"
                                            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                        {{ $active ? __('messages.subscription.switch_button') : __('messages.subscription.subscribe_button') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
