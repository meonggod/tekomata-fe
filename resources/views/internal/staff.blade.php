<x-layouts.internal :title="'Staff · tekomata internal'">
    @php
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
    @endphp

    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.staff.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.staff.subtitle') }}</p>
    </div>

    @error('email')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    {{-- Staff roster ---------------------------------------------------------}}
    <section class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.staff.roster') }}</h2>
        </header>

        @if (empty($staff))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.staff.empty') }}</p>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($staff as $s)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $s['email'] ?? '—' }}</span>
                            @if (($s['email'] ?? null) === $currentEmail)
                                <span class="ml-1 text-xs text-gray-400">({{ __('messages.internal.staff.you') }})</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                'bg-indigo-100 text-indigo-700' => ($s['role'] ?? '') === 'superadmin',
                                'bg-gray-100 text-gray-600' => ($s['role'] ?? '') !== 'superadmin',
                            ])>{{ $s['role'] ?? 'ops' }}</span>
                            @if (! ($s['active'] ?? true))
                                <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">{{ __('messages.internal.staff.pending') }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($isSuperadmin)
            <details class="border-t border-gray-100 px-5 py-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.staff.invite') }}</summary>
                <form method="POST" action="{{ route('internal.staff.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grow"><x-internal.field name="email" type="email" :label="__('messages.internal.staff.col_email')" /></div>
                    <div>
                        <label for="role" class="block text-xs font-medium text-gray-600">{{ __('messages.internal.staff.col_role') }}</label>
                        <select id="role" name="role" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            <option value="ops">ops</option>
                            <option value="superadmin">superadmin</option>
                        </select>
                    </div>
                    <x-internal.save-button :label="__('messages.internal.staff.invite')" />
                </form>
                <p class="mt-2 text-xs text-gray-400">{{ __('messages.internal.staff.invite_help') }}</p>
            </details>
        @endif
    </section>

    {{-- Audit log ------------------------------------------------------------}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.staff.audit.title') }}</h2>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.internal.staff.audit.help') }}</p>
        </header>

        @if (empty($audit))
            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('messages.internal.staff.audit.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-2.5">{{ __('messages.internal.staff.audit.col_when') }}</th>
                            <th class="px-5 py-2.5">{{ __('messages.internal.staff.audit.col_who') }}</th>
                            <th class="px-5 py-2.5">{{ __('messages.internal.staff.audit.col_action') }}</th>
                            <th class="px-5 py-2.5">{{ __('messages.internal.staff.audit.col_entity') }}</th>
                            <th class="px-5 py-2.5 text-right">{{ __('messages.internal.staff.audit.col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($audit as $a)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $when($a['created_at'] ?? null) }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $a['staff_email'] ?? ($a['staff_id'] ?? '—') }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $a['action'] ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $a['entity'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    @php $st = (int) ($a['status'] ?? 0); @endphp
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $st >= 200 && $st < 300,
                                        'bg-red-50 text-red-700' => $st >= 400,
                                        'bg-gray-100 text-gray-500' => $st < 200 || ($st >= 300 && $st < 400),
                                    ])>{{ $st ?: '—' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.internal>
