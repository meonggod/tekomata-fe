<x-layouts.app :title="__('messages.referral.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.referral.title')],
        ]" />
    </x-slot:breadcrumbs>

    @php
        $money = fn ($v) => __('messages.referral.money_prefix') . ' ' . number_format((float) $v, 0, ',', '.');

        // Translate a referral status enum with a safe fall-back to the raw value.
        $statusLabel = function (?string $key) {
            if ($key === null || $key === '') {
                return '—';
            }
            $full = "messages.referral.status.$key";
            $translated = __($full);
            return $translated === $full ? $key : $translated;
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
    @endphp

    <div class="space-y-6">
        <p class="text-sm text-gray-600">{{ __('messages.referral.subtitle') }}</p>

        {{-- ===== Share + total reward ===== --}}
        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Share card --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-medium text-gray-500">{{ __('messages.referral.share.label') }}</h2>

                @if ($code)
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span class="rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-lg font-semibold tracking-wider text-gray-900">{{ $code }}</span>
                        <button type="button"
                                data-copy="{{ $code }}" data-copied-label="{{ __('messages.referral.share.copied') }}"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            <span data-copy-label>{{ __('messages.referral.share.copy_code') }}</span>
                        </button>
                    </div>

                    @if ($shareUrl)
                        <div class="mt-4">
                            <label class="block text-xs font-medium text-gray-500">{{ __('messages.referral.share.link_label') }}</label>
                            <div class="mt-1.5 flex items-stretch gap-2">
                                <input type="text" readonly value="{{ $shareUrl }}"
                                       class="block w-full truncate rounded-lg border border-gray-300 bg-gray-50 py-2 px-3 text-sm text-gray-700 shadow-sm focus:outline-none"
                                       onclick="this.select()">
                                <button type="button"
                                        data-copy="{{ $shareUrl }}" data-copied-label="{{ __('messages.referral.share.copied') }}"
                                        class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                    <span data-copy-label>{{ __('messages.referral.share.copy_link') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <p class="mt-4 text-xs text-gray-500">{{ __('messages.referral.share.hint') }}</p>
                @else
                    <p class="mt-3 text-sm text-gray-400">{{ __('messages.referral.share.unavailable') }}</p>
                @endif
            </section>

            {{-- Total reward --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-medium text-gray-500">{{ __('messages.referral.reward.label') }}</h2>
                <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $money($totalReward) }}</p>
                <p class="mt-1.5 text-xs text-gray-500">
                    {{ __('messages.referral.reward.hint') }}
                    <a href="{{ route('wallet.index') }}" class="font-medium text-indigo-600 hover:underline">{{ __('messages.referral.reward.wallet_link') }}</a>
                </p>
            </section>
        </div>

        {{-- ===== Referred companies ===== --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">{{ __('messages.referral.list.title') }}</h2>
            </div>

            @if (empty($referrals))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.referral.list.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-3">{{ __('messages.referral.list.col_company') }}</th>
                                <th class="px-5 py-3">{{ __('messages.referral.list.col_status') }}</th>
                                <th class="px-5 py-3">{{ __('messages.referral.list.col_joined') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('messages.referral.list.col_reward') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($referrals as $r)
                                @php $status = $r['status'] ?? null; @endphp
                                <tr>
                                    <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $r['referee_company_id'] ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $status === 'rewarding',
                                            'bg-indigo-50 text-indigo-700' => $status === 'attributed',
                                            'bg-gray-100 text-gray-500' => ! in_array($status, ['rewarding', 'attributed'], true),
                                        ])>{{ $statusLabel($status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $when($r['created_at'] ?? null) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-gray-900">{{ $money($r['accrued_reward_total'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
