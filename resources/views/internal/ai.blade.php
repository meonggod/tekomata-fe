<x-layouts.internal :title="'AI catalog · tekomata internal'">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.ai.title') }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.ai.subtitle') }}</p>
        </div>
        @if ($isSuperadmin)
            <form method="POST" action="{{ route('internal.ai.sync') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                    {{ __('messages.internal.ai.sync_now') }}
                </button>
            </form>
        @endif
    </div>

    @error('form')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('ai_sync')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if ($syncSummary)
        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
            {{ __('messages.internal.ai.sync_summary', [
                'added' => $syncSummary['added'] ?? 0,
                'removed' => $syncSummary['removed'] ?? 0,
                'unchanged' => $syncSummary['unchanged'] ?? 0,
                'failures' => $syncSummary['failures'] ?? 0,
            ]) }}
        </div>
    @endif

    @unless ($isSuperadmin)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ __('messages.internal.read_only_notice') }}</div>
    @endunless

    {{-- Pending-review queue: auto-discovered models awaiting a price ----------}}
    @if (! empty($pending))
        <section class="mb-8 overflow-hidden rounded-xl border border-amber-200 bg-amber-50/40 shadow-sm">
            <header class="border-b border-amber-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-amber-900">{{ __('messages.internal.ai.pending.title') }}</h2>
                <p class="mt-0.5 text-xs text-amber-700">{{ __('messages.internal.ai.pending.help') }}</p>
            </header>
            <ul class="divide-y divide-amber-100">
                @foreach ($pending as $m)
                    <li class="px-5 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $m['name'] ?? ($m['model_id'] ?? '—') }}</p>
                                <p class="font-mono text-xs text-gray-500">{{ $m['provider'] ?? '' }} · {{ $m['model_id'] ?? '' }}</p>
                            </div>
                            @if ($isSuperadmin)
                                <details class="text-right">
                                    <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.ai.pending.price') }}</summary>
                                    <form method="POST" action="{{ route('internal.ai.models.price', $m['id'] ?? '') }}" class="mt-3 grid gap-2 text-left sm:grid-cols-2">
                                        @csrf
                                        <x-internal.field name="input_price" type="number" step="0.000001" :label="__('messages.internal.ai.col_input')" />
                                        <x-internal.field name="output_price" type="number" step="0.000001" :label="__('messages.internal.ai.col_output')" />
                                        <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.ai.col_active') }}</label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="user_selectable" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.ai.col_selectable') }}</label>
                                        <div class="sm:col-span-2"><x-internal.save-button :label="__('messages.internal.ai.pending.price_activate')" /></div>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Providers ------------------------------------------------------------}}
    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.ai.providers.title') }}</h2>
        </header>

        @if (empty($providers))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.ai.providers.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-2.5">{{ __('messages.internal.ai.providers.col_name') }}</th>
                            <th class="px-5 py-2.5 text-center">{{ __('messages.internal.ai.providers.col_credential') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.ai.providers.col_priority') }}</th>
                            @if ($isSuperadmin)<th class="px-5 py-2.5"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($providers as $pv)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $pv['name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($pv['has_credential'] ?? false)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('messages.internal.ai.providers.set') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">{{ __('messages.internal.ai.providers.unset') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $pv['priority'] ?? '—' }}</td>
                                @if ($isSuperadmin)
                                    <td class="px-5 py-3 text-right">
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.actions.edit') }}</summary>
                                            <form method="POST" action="{{ route('internal.ai.providers.update', $pv['id'] ?? '') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @csrf @method('PUT')
                                                <x-internal.field name="priority" type="number" step="1" :label="__('messages.internal.ai.providers.col_priority')" :value="$pv['priority'] ?? ''" />
                                                <x-internal.field name="credential" type="password" :label="__('messages.internal.ai.providers.rotate_credential')" autocomplete="new-password" />
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
                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.ai.providers.add') }}</summary>
                <form method="POST" action="{{ route('internal.ai.providers.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <x-internal.field name="name" :label="__('messages.internal.ai.providers.col_name')" />
                    <x-internal.field name="priority" type="number" step="1" :label="__('messages.internal.ai.providers.col_priority')" value="0" />
                    <div class="sm:col-span-2"><x-internal.field name="credential" type="password" :label="__('messages.internal.ai.providers.col_credential')" autocomplete="new-password" /></div>
                    <div class="sm:col-span-2"><x-internal.save-button :label="__('messages.internal.ai.providers.add')" /></div>
                </form>
            </details>
        @endif
    </section>

    {{-- Model catalog --------------------------------------------------------}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.ai.models.title') }}</h2>
        </header>

        @if (empty($models))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.ai.models.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-2.5">{{ __('messages.internal.ai.models.col_model') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.ai.col_input') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.ai.col_output') }}</th>
                            <th class="px-5 py-2.5 text-center">{{ __('messages.internal.ai.col_active') }}</th>
                            <th class="px-5 py-2.5 text-center">{{ __('messages.internal.ai.col_selectable') }}</th>
                            @if ($isSuperadmin)<th class="px-5 py-2.5"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($models as $m)
                            @php $priced = (bool) ($m['priced'] ?? true); @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="font-medium text-gray-900">{{ $m['name'] ?? ($m['model_id'] ?? '—') }}</span>
                                    <span class="block font-mono text-xs text-gray-400">{{ $m['provider'] ?? '' }} · {{ $m['model_id'] ?? '' }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $priced ? ($m['input_price'] ?? '—') : '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-gray-700">{{ $priced ? ($m['output_price'] ?? '—') : '—' }}</td>
                                <td class="px-5 py-3 text-center"><x-internal.active-badge :active="(bool) ($m['is_active'] ?? false)" /></td>
                                <td class="px-5 py-3 text-center"><x-internal.active-badge :active="(bool) ($m['user_selectable'] ?? false)" /></td>
                                @if ($isSuperadmin)
                                    <td class="px-5 py-3 text-right">
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.actions.edit') }}</summary>
                                            <form method="POST" action="{{ route('internal.ai.models.price', $m['id'] ?? '') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @csrf
                                                <x-internal.field name="input_price" type="number" step="0.000001" :label="__('messages.internal.ai.col_input')" :value="$m['input_price'] ?? ''" />
                                                <x-internal.field name="output_price" type="number" step="0.000001" :label="__('messages.internal.ai.col_output')" :value="$m['output_price'] ?? ''" />
                                                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" @checked($m['is_active'] ?? false) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.ai.col_active') }}</label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="user_selectable" value="1" @checked($m['user_selectable'] ?? false) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ __('messages.internal.ai.col_selectable') }}</label>
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
    </section>
</x-layouts.internal>
