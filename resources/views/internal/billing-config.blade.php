<x-layouts.internal :title="'Billing config · tekomata internal'">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.billing.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.billing.subtitle') }}</p>
    </div>

    @error('form')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('settings')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @unless ($isSuperadmin)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('messages.internal.read_only_notice') }}
        </div>
    @endunless

    {{-- Subscription plans ---------------------------------------------------}}
    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.billing.plans.title') }}</h2>
        </header>

        @if (empty($plans))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.billing.plans.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-2.5">{{ __('messages.internal.billing.plans.col_name') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.billing.plans.col_price') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.billing.plans.col_base_rate') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.billing.plans.col_referral') }}</th>
                            <th class="px-5 py-2.5 text-center">{{ __('messages.internal.billing.plans.col_active') }}</th>
                            @if ($isSuperadmin)<th class="px-5 py-2.5"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($plans as $p)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $p['name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $p['monthly_price'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $p['base_rate'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $p['referral_pct'] ?? '0' }}%</td>
                                <td class="px-5 py-3 text-center">
                                    <x-internal.active-badge :active="(bool) ($p['is_active'] ?? false)" />
                                </td>
                                @if ($isSuperadmin)
                                    <td class="px-5 py-3 text-right">
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.actions.edit') }}</summary>
                                            <form method="POST" action="{{ route('internal.billing.plans.update', $p['id'] ?? '') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @csrf @method('PUT')
                                                <x-internal.field name="name" :label="__('messages.internal.billing.plans.col_name')" :value="$p['name'] ?? ''" />
                                                <x-internal.field name="monthly_price" type="number" step="0.01" :label="__('messages.internal.billing.plans.col_price')" :value="$p['monthly_price'] ?? ''" />
                                                <x-internal.field name="base_rate" type="number" step="0.0001" :label="__('messages.internal.billing.plans.col_base_rate')" :value="$p['base_rate'] ?? ''" />
                                                <x-internal.field name="referral_pct" type="number" step="0.1" :label="__('messages.internal.billing.plans.col_referral')" :value="$p['referral_pct'] ?? ''" />
                                                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" @checked($p['is_active'] ?? false) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.billing.plans.col_active') }}</label>
                                                <div class="sm:col-span-2"><x-internal.save-button /></div>
                                            </form>
                                        </details>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($isSuperadmin)
            <details class="border-t border-gray-100 px-5 py-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.billing.plans.add') }}</summary>
                <form method="POST" action="{{ route('internal.billing.plans.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <x-internal.field name="name" :label="__('messages.internal.billing.plans.col_name')" />
                    <x-internal.field name="monthly_price" type="number" step="0.01" :label="__('messages.internal.billing.plans.col_price')" />
                    <x-internal.field name="base_rate" type="number" step="0.0001" :label="__('messages.internal.billing.plans.col_base_rate')" />
                    <x-internal.field name="referral_pct" type="number" step="0.1" :label="__('messages.internal.billing.plans.col_referral')" value="0" />
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.billing.plans.col_active') }}</label>
                    <div class="sm:col-span-2"><x-internal.save-button :label="__('messages.internal.billing.plans.add')" /></div>
                </form>
            </details>
        @endif
    </section>

    {{-- Feature pricing ------------------------------------------------------}}
    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.billing.features.title') }}</h2>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.internal.billing.features.help') }}</p>
        </header>

        @if (empty($features))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.billing.features.empty') }}</p>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($features as $f)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $f['label'] ?? ($f['key'] ?? '—') }}</p>
                            <p class="font-mono text-xs text-gray-400">{{ $f['key'] ?? '' }}</p>
                        </div>
                        @if ($isSuperadmin)
                            <form method="POST" action="{{ route('internal.billing.features.update', $f['key'] ?? '') }}" class="flex items-end gap-2">
                                @csrf @method('PUT')
                                <input type="number" name="price" step="0.0001" min="0" value="{{ $f['price'] ?? '' }}"
                                       class="w-32 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-mono shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <button type="submit" class="rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-gray-700">{{ __('messages.internal.actions.save') }}</button>
                            </form>
                        @else
                            <span class="font-mono text-sm text-gray-700">{{ $f['price'] ?? '—' }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Promo codes ----------------------------------------------------------}}
    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.billing.promos.title') }}</h2>
        </header>

        @if (empty($promoCodes))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.billing.promos.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-2.5">{{ __('messages.internal.billing.promos.col_code') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.billing.promos.col_amount') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.billing.promos.col_cap') }}</th>
                            <th class="px-5 py-2.5 text-center">{{ __('messages.internal.billing.promos.col_active') }}</th>
                            @if ($isSuperadmin)<th class="px-5 py-2.5"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($promoCodes as $c)
                            <tr>
                                <td class="px-5 py-3 font-mono font-medium text-gray-900">{{ $c['code'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $c['amount'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right text-gray-700">{{ $c['usage_cap'] ?? '∞' }}</td>
                                <td class="px-5 py-3 text-center"><x-internal.active-badge :active="(bool) ($c['is_active'] ?? false)" /></td>
                                @if ($isSuperadmin)
                                    <td class="px-5 py-3 text-right">
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.actions.edit') }}</summary>
                                            <form method="POST" action="{{ route('internal.billing.promos.update', $c['id'] ?? '') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @csrf @method('PUT')
                                                <x-internal.field name="amount" type="number" step="0.01" :label="__('messages.internal.billing.promos.col_amount')" :value="$c['amount'] ?? ''" />
                                                <x-internal.field name="usage_cap" type="number" step="1" :label="__('messages.internal.billing.promos.col_cap')" :value="$c['usage_cap'] ?? ''" />
                                                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" @checked($c['is_active'] ?? false) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.billing.promos.col_active') }}</label>
                                                <div class="sm:col-span-2"><x-internal.save-button /></div>
                                            </form>
                                        </details>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($isSuperadmin)
            <details class="border-t border-gray-100 px-5 py-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.billing.promos.add') }}</summary>
                <form method="POST" action="{{ route('internal.billing.promos.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <x-internal.field name="code" :label="__('messages.internal.billing.promos.col_code')" />
                    <x-internal.field name="amount" type="number" step="0.01" :label="__('messages.internal.billing.promos.col_amount')" />
                    <x-internal.field name="valid_from" type="date" :label="__('messages.internal.billing.promos.col_from')" />
                    <x-internal.field name="valid_until" type="date" :label="__('messages.internal.billing.promos.col_until')" />
                    <x-internal.field name="usage_cap" type="number" step="1" :label="__('messages.internal.billing.promos.col_cap')" />
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.billing.promos.col_active') }}</label>
                    <div class="sm:col-span-2"><x-internal.save-button :label="__('messages.internal.billing.promos.add')" /></div>
                </form>
            </details>
        @endif
    </section>

    {{-- Platform settings ----------------------------------------------------}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.billing.settings.title') }}</h2>
        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.internal.billing.settings.help') }}</p>

        @if ($isSuperadmin)
            <form method="POST" action="{{ route('internal.billing.settings.update') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf @method('PUT')
                <x-internal.field name="wallet_payout_min" type="number" step="0.01" :label="__('messages.internal.billing.settings.payout_min')" :value="old('wallet_payout_min', $settings['wallet_payout_min'] ?? '')" />
                <x-internal.field name="wallet_payout_fee" type="number" step="0.01" :label="__('messages.internal.billing.settings.payout_fee')" :value="old('wallet_payout_fee', $settings['wallet_payout_fee'] ?? '')" />
                <x-internal.field name="referral_reward_cap" type="number" step="0.01" :label="__('messages.internal.billing.settings.referral_cap')" :value="old('referral_reward_cap', $settings['referral_reward_cap'] ?? '')" />
                <x-internal.field name="fx_max_age_hours" type="number" step="0.5" :label="__('messages.internal.billing.settings.fx_max_age')" :value="old('fx_max_age_hours', $settings['fx_max_age_hours'] ?? '')" />
                <div class="sm:col-span-2 lg:col-span-4"><x-internal.save-button /></div>
            </form>
        @else
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['wallet_payout_min' => 'payout_min', 'wallet_payout_fee' => 'payout_fee', 'referral_reward_cap' => 'referral_cap', 'fx_max_age_hours' => 'fx_max_age'] as $key => $lbl)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                        <dt class="text-xs text-gray-500">{{ __('messages.internal.billing.settings.'.$lbl) }}</dt>
                        <dd class="font-mono font-medium text-gray-900">{{ $settings[$key] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>
</x-layouts.internal>
