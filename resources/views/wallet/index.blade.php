<x-layouts.app :title="__('messages.wallet.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.wallet.title')],
        ]" />
    </x-slot:breadcrumbs>

    @php
        $money = fn ($v) => __('messages.wallet.money_prefix') . ' ' . number_format((float) $v, 0, ',', '.');

        // Translate a ledger enum with a safe fall-back to the raw value.
        $label = function (string $group, ?string $key) {
            if ($key === null || $key === '') {
                return '—';
            }
            $full = "messages.wallet.$group.$key";
            $translated = __($full);
            return $translated === $full ? $key : $translated;
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

        $spendableVal = (float) $spendable;
        $isEmpty = $spendableVal <= 0;
        $isLow   = ! $isEmpty && $spendableVal < $lowThreshold;
        $errorSection = session('error_section');
    @endphp

    <div class="space-y-6">
        <p class="text-sm text-gray-600">{{ __('messages.wallet.subtitle') }}</p>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- ===== Balances ===== --}}
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Spendable --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-medium text-gray-500">{{ __('messages.wallet.spendable.label') }}</h2>
                <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $money($spendable) }}</p>
                <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.wallet.spendable.hint') }}</p>

                @if ($isEmpty)
                    <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                        {{ __('messages.wallet.spendable.empty_warning') }}
                    </div>
                @elseif ($isLow)
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        {{ __('messages.wallet.spendable.low_warning') }}
                    </div>
                @endif
            </section>

            {{-- Reward --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-medium text-gray-500">{{ __('messages.wallet.reward.label') }}</h2>
                <p class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ $money($reward) }}</p>
                <p class="mt-1.5 text-xs text-gray-500">{{ __('messages.wallet.reward.hint') }}</p>
            </section>
        </div>

        {{-- ===== Actions ===== --}}
        <div class="space-y-3">
            {{-- Top up --}}
            <details class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" {{ $errorSection === 'topup' ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ __('messages.wallet.topup.title') }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.wallet.topup.subtitle') }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <form method="POST" action="{{ route('wallet.topup') }}" class="space-y-4 border-t border-gray-100 px-5 py-5">
                    @csrf
                    @if ($errorSection === 'topup')
                        <x-wallet.error :error="$errors->first('amount') ?: $errors->first('wallet_action')" />
                    @endif
                    <div>
                        <label for="topup_amount" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.topup.amount_label') }}</label>
                        <input type="text" inputmode="numeric" id="topup_amount" name="amount"
                               value="{{ $errorSection === 'topup' ? old('amount') : '' }}"
                               placeholder="{{ __('messages.wallet.topup.amount_placeholder') }}"
                               class="mt-1.5 block w-full max-w-xs rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    </div>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.wallet.topup.submit') }}
                    </button>
                </form>
            </details>

            {{-- Convert --}}
            <details class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" {{ $errorSection === 'convert' ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ __('messages.wallet.convert.title') }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.wallet.convert.subtitle') }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <form method="POST" action="{{ route('wallet.convert') }}" class="space-y-4 border-t border-gray-100 px-5 py-5">
                    @csrf
                    @if ($errorSection === 'convert')
                        <x-wallet.error :error="$errors->first('amount') ?: $errors->first('wallet_action')" />
                    @endif
                    <div>
                        <label for="convert_amount" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.convert.amount_label') }}</label>
                        <input type="text" inputmode="numeric" id="convert_amount" name="amount"
                               value="{{ $errorSection === 'convert' ? old('amount') : '' }}"
                               placeholder="{{ __('messages.wallet.convert.amount_placeholder') }}"
                               class="mt-1.5 block w-full max-w-xs rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    </div>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                        {{ __('messages.wallet.convert.submit') }}
                    </button>
                </form>
            </details>

            {{-- Withdraw --}}
            <details class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" {{ $errorSection === 'withdraw' ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ __('messages.wallet.withdraw.title') }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.wallet.withdraw.subtitle') }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>

                @unless ($kybVerified)
                    <div class="border-t border-gray-100 px-5 py-5">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ __('messages.wallet.withdraw.gated') }}
                            <a href="{{ route('settings.show') . '?tab=kyckyb' }}" class="font-medium text-indigo-600 hover:underline">
                                {{ __('messages.wallet.withdraw.gated_link') }}
                            </a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('wallet.withdraw') }}" class="space-y-4 border-t border-gray-100 px-5 py-5">
                        @csrf
                        @if ($errorSection === 'withdraw')
                            <x-wallet.error :error="$errors->first('amount') ?: ($errors->first('bank_code') ?: ($errors->first('account_number') ?: ($errors->first('account_holder') ?: $errors->first('wallet_action'))))" />
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="wd_amount" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.withdraw.amount_label') }}</label>
                                <input type="text" inputmode="numeric" id="wd_amount" name="amount"
                                       value="{{ $errorSection === 'withdraw' ? old('amount') : '' }}"
                                       placeholder="{{ __('messages.wallet.withdraw.amount_placeholder') }}"
                                       class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            </div>
                            <div>
                                <label for="wd_bank" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.withdraw.bank_code_label') }}</label>
                                <input type="text" id="wd_bank" name="bank_code" list="bank-codes"
                                       value="{{ $errorSection === 'withdraw' ? old('bank_code') : '' }}"
                                       placeholder="{{ __('messages.wallet.withdraw.bank_code_placeholder') }}"
                                       class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <datalist id="bank-codes">
                                    <option value="BCA"><option value="BNI"><option value="BRI"><option value="MANDIRI">
                                    <option value="CIMB"><option value="PERMATA"><option value="DANAMON"><option value="BSI">
                                </datalist>
                            </div>
                            <div>
                                <label for="wd_acc_no" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.withdraw.account_number_label') }}</label>
                                <input type="text" inputmode="numeric" id="wd_acc_no" name="account_number"
                                       value="{{ $errorSection === 'withdraw' ? old('account_number') : '' }}"
                                       class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            </div>
                            <div>
                                <label for="wd_acc_holder" class="block text-sm font-medium text-gray-700">{{ __('messages.wallet.withdraw.account_holder_label') }}</label>
                                <input type="text" id="wd_acc_holder" name="account_holder"
                                       value="{{ $errorSection === 'withdraw' ? old('account_holder') : '' }}"
                                       class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            </div>
                        </div>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.wallet.withdraw.submit') }}
                        </button>
                    </form>
                @endunless
            </details>
        </div>

        {{-- ===== History ===== --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">{{ __('messages.wallet.history.title') }}</h2>
            </div>

            @if (empty($transactions))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.wallet.history.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-3">{{ __('messages.wallet.history.col_date') }}</th>
                                <th class="px-5 py-3">{{ __('messages.wallet.history.col_type') }}</th>
                                <th class="px-5 py-3">{{ __('messages.wallet.history.col_bucket') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('messages.wallet.history.col_amount') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('messages.wallet.history.col_balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($transactions as $tx)
                                @php
                                    $amount = (float) ($tx['amount'] ?? 0);
                                    $bucket = $tx['bucket'] ?? null;
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $when($tx['created_at'] ?? null) }}</td>
                                    <td class="px-5 py-3 text-gray-900">{{ $label('type', $tx['type'] ?? null) }}</td>
                                    <td class="px-5 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-indigo-50 text-indigo-700' => $bucket === 'spendable',
                                            'bg-emerald-50 text-emerald-700' => $bucket === 'reward',
                                            'bg-gray-100 text-gray-600' => ! in_array($bucket, ['spendable', 'reward'], true),
                                        ])>{{ $label('bucket', $bucket) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-medium {{ $amount < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        {{ $amount >= 0 ? '+' : '' }}{{ $money($tx['amount'] ?? 0) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-gray-500">{{ $money($tx['balance'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($offset > 0 || $hasMore)
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                        @if ($offset > 0)
                            <a href="{{ route('wallet.index', ['offset' => max(0, $offset - $pageSize)]) }}"
                               class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                ← {{ __('messages.wallet.history.prev') }}
                            </a>
                        @else
                            <span></span>
                        @endif

                        @if ($hasMore)
                            <a href="{{ route('wallet.index', ['offset' => $offset + $pageSize]) }}"
                               class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                {{ __('messages.wallet.history.next') }} →
                            </a>
                        @else
                            <span></span>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-layouts.app>
