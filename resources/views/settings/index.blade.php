<x-layouts.app :title="__('messages.settings.title') . ' · tekomata'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => __('messages.nav.dashboard'), 'url' => route('dashboard')],
            ['label' => __('messages.settings.title')],
        ]" />
    </x-slot:breadcrumbs>

    @php
        $bh       = $settings['business_hours'] ?? null;
        $hasOld   = old('business_hours_mode') !== null;
        $bhMode   = old('business_hours_mode', $bh === null ? 'always_active' : 'scheduled');
        $bhDays   = [];
        $bhSlots  = [];
        $allDays  = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($allDays as $d) {
            $apiSlots     = $bh[$d] ?? [];
            $bhDays[$d]   = $hasOld ? (old("bh_enabled.$d") === '1') : count($apiSlots) > 0;
            $bhSlots[$d]  = $hasOld ? (old("bh.$d") ?: []) : $apiSlots;
        }

        $activeTab = request('tab', 'company');
        if (! in_array($activeTab, ['company', 'user', 'kyckyb'])) {
            $activeTab = 'company';
        }

        $kyc = $settings['kyc'] ?? null;
        $kyb = $settings['kyb'] ?? null;

        $idTypeLabels = [
            'ktp'      => __('messages.onboarding.id_type_ktp'),
            'passport' => __('messages.onboarding.id_type_passport'),
            'sim'      => __('messages.onboarding.id_type_sim'),
        ];
        $bizTypeLabels = [
            'pt'          => __('messages.onboarding.business_type_pt'),
            'cv'          => __('messages.onboarding.business_type_cv'),
            'ud'          => __('messages.onboarding.business_type_ud'),
            'perorangan'  => __('messages.onboarding.business_type_perorangan'),
            'other'       => __('messages.onboarding.business_type_other'),
        ];
    @endphp

    <div class="mx-auto w-full max-w-3xl space-y-6">
        <p class="text-sm text-gray-600">{{ __('messages.settings.subtitle') }}</p>

        {{-- ===== Tab Bar ===== --}}
        <nav class="flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1" role="tablist">
            @foreach (['company', 'user', 'kyckyb'] as $tab)
                <button type="button"
                        role="tab"
                        aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"
                        aria-controls="panel-{{ $tab }}"
                        data-tab="{{ $tab }}"
                        @class([
                            'flex-1 rounded-md px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1',
                            'bg-white text-gray-900 shadow-sm' => $activeTab === $tab,
                            'text-gray-500 hover:text-gray-700 hover:bg-white/50' => $activeTab !== $tab,
                        ])>
                    {{ __("messages.settings.tabs.$tab") }}
                </button>
            @endforeach
        </nav>

        {{-- ===== Company Tab ===== --}}
        <div id="panel-company" role="tabpanel" @class(['space-y-6', 'hidden' => $activeTab !== 'company'])>

            {{-- Company Identity --}}
            <section id="company" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.company.title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.settings.company.subtitle') }}</p>
                </div>

                @if (session('status_section') === 'company' && session('status'))
                    <div class="border-b border-green-100 bg-green-50 px-5 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-4 px-5 py-5">
                    @csrf

                    @if ($errors->hasAny(['display_name', 'country_code', 'timezone'])
                            && (session('error_section') === 'company' || ! session('error_section')))
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {{ $errors->first('display_name') ?: ($errors->first('country_code') ?: $errors->first('timezone')) }}
                        </div>
                    @endif

                    {{-- Display name --}}
                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.company.display_name_label') }}
                        </label>
                        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.settings.company.display_name_hint') }}</p>
                        <input type="text" id="display_name" name="display_name"
                               value="{{ old('display_name', $settings['display_name'] ?? '') }}"
                               placeholder="{{ __('messages.settings.company.display_name_placeholder') }}"
                               class="mt-1.5 block w-full rounded-lg border bg-white py-2.5 pl-3 pr-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $errors->has('display_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                    </div>

                    {{-- Country --}}
                    <div>
                        <label for="country_code" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.company.country_label') }}
                        </label>
                        <x-country-select
                            :countries="$countries"
                            name="country_code"
                            :selected="old('country_code', $settings['country']['code'] ?? '')" />
                        @error('country_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.company.timezone_label') }}
                        </label>
                        <input type="text" id="timezone" name="timezone" list="timezone-list"
                               value="{{ old('timezone', $settings['timezone'] ?? '') }}"
                               placeholder="{{ __('messages.settings.company.timezone_placeholder') }}"
                               autocomplete="off"
                               class="mt-1.5 block w-full rounded-lg border bg-white py-2.5 pl-3 pr-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $errors->has('timezone') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                        <datalist id="timezone-list">
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}">
                            @endforeach
                        </datalist>
                        @error('timezone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.settings.company.save') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Assistant --}}
            <section id="assistant" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.assistant.title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.settings.assistant.subtitle') }}</p>
                </div>

                @if (session('status_section') === 'assistant' && session('status'))
                    <div class="border-b border-green-100 bg-green-50 px-5 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form id="assistant-form" method="POST" action="{{ route('settings.assistant.update') }}" class="space-y-5 px-5 py-5">
                    @csrf

                    @if ($errors->hasAny(['assistant_persona_name', 'reply_language', 'business_hours_mode', 'out_of_hours_message'])
                            && (session('error_section') === 'assistant' || ! session('error_section')))
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {{ $errors->first('assistant_persona_name')
                                ?: ($errors->first('reply_language')
                                ?: ($errors->first('business_hours_mode')
                                ?: $errors->first('out_of_hours_message'))) }}
                        </div>
                    @endif

                    {{-- Persona name --}}
                    <div>
                        <label for="assistant_persona_name" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.assistant.persona_label') }}
                        </label>
                        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.settings.assistant.persona_hint') }}</p>
                        <input type="text" id="assistant_persona_name" name="assistant_persona_name"
                               value="{{ old('assistant_persona_name', $settings['assistant_persona_name'] ?? '') }}"
                               placeholder="{{ __('messages.settings.assistant.persona_placeholder') }}"
                               class="mt-1.5 block w-full rounded-lg border bg-white py-2.5 pl-3 pr-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $errors->has('assistant_persona_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                        @error('assistant_persona_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Reply language --}}
                    <div>
                        <label for="reply_language" class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.assistant.language_label') }}
                        </label>
                        <select id="reply_language" name="reply_language"
                                class="mt-1.5 block w-full rounded-lg border bg-white py-2.5 pl-3 pr-8 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $errors->has('reply_language') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                            <option value="auto" @selected(old('reply_language', $settings['reply_language'] ?? 'auto') === 'auto')>
                                {{ __('messages.settings.assistant.language_auto') }}
                            </option>
                            <option value="id" @selected(old('reply_language', $settings['reply_language'] ?? 'auto') === 'id')>
                                {{ __('messages.settings.assistant.language_id') }}
                            </option>
                            <option value="en" @selected(old('reply_language', $settings['reply_language'] ?? 'auto') === 'en')>
                                {{ __('messages.settings.assistant.language_en') }}
                            </option>
                        </select>
                    </div>

                    {{-- Business hours --}}
                    <div>
                        <p class="block text-sm font-medium text-gray-700">{{ __('messages.settings.assistant.hours_label') }}</p>
                        <div class="mt-2 space-y-2">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-700">
                                <input type="radio" name="business_hours_mode" value="always_active"
                                       @checked($bhMode === 'always_active')
                                       class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('messages.settings.assistant.hours_always_active') }}
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-700">
                                <input type="radio" name="business_hours_mode" value="scheduled"
                                       @checked($bhMode === 'scheduled')
                                       class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('messages.settings.assistant.hours_scheduled') }}
                            </label>
                        </div>

                        {{-- Per-day slots --}}
                        <div id="bh-scheduled" @class(['mt-4 space-y-3 rounded-lg border border-gray-200 p-4', 'hidden' => $bhMode !== 'scheduled'])>
                            @foreach ($allDays as $day)
                                <div data-bh-day="{{ $day }}">
                                    <label class="flex items-center gap-2.5 text-sm font-medium text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="bh_enabled[{{ $day }}]" value="1"
                                               data-bh-toggle
                                               @checked($bhDays[$day])
                                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('messages.settings.assistant.days.'.$day) }}
                                    </label>
                                    <div data-bh-slots
                                         data-bh-next-idx="{{ count($bhSlots[$day]) }}"
                                         @class(['mt-2 ml-6 space-y-2', 'hidden' => ! $bhDays[$day]])>
                                        @foreach ($bhSlots[$day] as $i => $slot)
                                            <div data-bh-slot class="flex items-center gap-2">
                                                <input type="time" name="bh[{{ $day }}][{{ $i }}][open]"
                                                       value="{{ $slot['open'] ?? '' }}"
                                                       class="w-28 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40">
                                                <span class="shrink-0 text-xs text-gray-400">–</span>
                                                <input type="time" name="bh[{{ $day }}][{{ $i }}][close]"
                                                       value="{{ $slot['close'] ?? '' }}"
                                                       class="w-28 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40">
                                                <button type="button" data-bh-remove
                                                        class="rounded p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                                        aria-label="{{ __('messages.settings.assistant.add_slot') }}">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                        <button type="button" data-bh-add="{{ $day }}"
                                                class="flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ __('messages.settings.assistant.add_slot') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Out-of-hours message --}}
                        <div id="bh-ooh" @class(['mt-4', 'hidden' => $bhMode !== 'scheduled'])>
                            <label for="out_of_hours_message" class="block text-sm font-medium text-gray-700">
                                {{ __('messages.settings.assistant.ooh_label') }}
                            </label>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.settings.assistant.ooh_hint') }}</p>
                            <textarea id="out_of_hours_message" name="out_of_hours_message" rows="3"
                                      placeholder="{{ __('messages.settings.assistant.ooh_placeholder') }}"
                                      class="mt-1.5 block w-full rounded-lg border bg-white py-2.5 pl-3 pr-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 {{ $errors->has('out_of_hours_message') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">{{ old('out_of_hours_message', $settings['out_of_hours_message'] ?? '') }}</textarea>
                            @error('out_of_hours_message')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.settings.assistant.save') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Web Chat Widget --}}
            <section id="widget" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.widget.title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.settings.widget.subtitle') }}</p>
                </div>

                <div class="px-5 py-5 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ __('messages.settings.widget.embed_label') }}
                        </label>
                        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.settings.widget.embed_hint') }}</p>
                    </div>

                    @php($embedSnippet = '<script src="' . url('/js/widget.js') . '" data-site-key="' . e($companyId) . '"></script>')

                    <div class="relative">
                        <pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 font-mono select-all"><code>{{ $embedSnippet }}</code></pre>
                        <button type="button"
                                data-copy="{{ $embedSnippet }}"
                                data-copied-label="{{ __('messages.settings.widget.copied') }}"
                                class="absolute top-3 right-3 rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                            <span data-copy-label>{{ __('messages.settings.widget.copy') }}</span>
                        </button>
                    </div>
                </div>
            </section>

        </div>

        {{-- ===== User Tab ===== --}}
        <div id="panel-user" role="tabpanel" @class(['space-y-6', 'hidden' => $activeTab !== 'user'])>

            {{-- Billing --}}
            <section id="billing" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.billing.title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.settings.billing.subtitle') }}</p>
                </div>

                @if (session('status_section') === 'billing' && session('status'))
                    <div class="border-b border-green-100 bg-green-50 px-5 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @error('email_action')
                    <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</div>
                @enderror

                <div class="divide-y divide-gray-100">

                    {{-- Billing currency (read-only) --}}
                    <div class="px-5 py-4">
                        <p class="text-sm font-medium text-gray-700">{{ __('messages.settings.billing.currency_label') }}</p>
                        @php($bc = $settings['billing_currency'] ?? null)
                        @if ($bc)
                            <div class="mt-2 flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700">
                                    {{ $bc['symbol'] ?? $bc['code'] ?? '?' }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $bc['code'] ?? '' }}</p>
                                    <p class="text-xs text-gray-500">{{ $bc['name'] ?? '' }}</p>
                                </div>
                            </div>
                        @else
                            <p class="mt-1 text-sm text-gray-500">{{ __('messages.settings.billing.no_currency') }}</p>
                        @endif
                        <p class="mt-2 text-xs text-gray-400">
                            {{ __('messages.settings.billing.currency_hint') }}
                            <a href="{{ route('currencies.index') }}" class="text-indigo-600 hover:underline">{{ __('messages.nav.currencies') }}</a>
                        </p>
                    </div>

                    {{-- Notification emails --}}
                    <div class="px-5 py-4">
                        <p class="text-sm font-medium text-gray-700">{{ __('messages.settings.billing.emails_label') }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.settings.billing.emails_hint') }}</p>

                        @php($emails = $settings['emails'] ?? [])

                        @if (empty($emails))
                            <p class="mt-3 text-sm text-gray-400">{{ __('messages.settings.billing.no_emails') }}</p>
                        @else
                            <ul class="mt-3 space-y-2">
                                @foreach ($emails as $entry)
                                    @php($isPrimary = ($entry['priority'] ?? 999) === 0)
                                    <li class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                        <span class="min-w-0 flex-1 text-sm text-gray-900 truncate">{{ $entry['email'] }}</span>
                                        @if ($isPrimary)
                                            <span class="shrink-0 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                {{ __('messages.settings.billing.primary_badge') }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('settings.emails.promote', ['id' => $entry['id']]) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="shrink-0 text-xs font-medium text-gray-500 hover:text-indigo-600 transition-colors">
                                                    {{ __('messages.settings.billing.promote') }}
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('settings.emails.delete', ['id' => $entry['id']]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('{{ __('messages.settings.billing.confirm_delete_email') }}')"
                                                    class="shrink-0 text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
                                                {{ __('messages.settings.billing.delete') }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Add email form --}}
                        <form method="POST" action="{{ route('settings.emails.add') }}" class="mt-4 flex gap-2">
                            @csrf
                            <div class="min-w-0 flex-1">
                                <input type="email" name="email" id="new_email"
                                       value="{{ old('new_email') }}"
                                       placeholder="{{ __('messages.settings.billing.add_email_placeholder') }}"
                                       class="block w-full rounded-lg border bg-white py-2 pl-3 pr-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 {{ $errors->has('new_email') || $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                                @error('new_email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                {{ __('messages.settings.billing.add_email_submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- WhatsApp Numbers --}}
            <section id="whatsapp" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.whatsapp.title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.settings.whatsapp.subtitle') }}</p>
                </div>

                @if (session('status_section') === 'whatsapp' && session('status'))
                    <div class="border-b border-green-100 bg-green-50 px-5 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @error('whatsapp_action')
                    <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm text-red-800">{{ $message }}</div>
                @enderror

                <div class="px-5 py-4">
                    @php($waNumbers = $settings['whatsapp_numbers'] ?? [])

                    @if (empty($waNumbers))
                        <p class="text-sm text-gray-400">{{ __('messages.settings.whatsapp.no_numbers') }}</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($waNumbers as $entry)
                                @php($isPrimary = ($entry['priority'] ?? 999) === 0)
                                <li class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                    <span class="min-w-0 flex-1 text-sm font-mono text-gray-900">{{ $entry['number'] }}</span>
                                    @if ($isPrimary)
                                        <span class="shrink-0 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                            {{ __('messages.settings.whatsapp.primary_badge') }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('settings.whatsapp.promote', ['id' => $entry['id']]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="shrink-0 text-xs font-medium text-gray-500 hover:text-indigo-600 transition-colors">
                                                {{ __('messages.settings.whatsapp.promote') }}
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('settings.whatsapp.delete', ['id' => $entry['id']]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('{{ __('messages.settings.whatsapp.confirm_delete') }}')"
                                                class="shrink-0 text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
                                            {{ __('messages.settings.whatsapp.delete') }}
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Add WhatsApp number form --}}
                    <form method="POST" action="{{ route('settings.whatsapp.add') }}" class="mt-4 flex gap-2">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <input type="tel" name="number" id="new_number"
                                   value="{{ old('new_number') }}"
                                   placeholder="{{ __('messages.settings.whatsapp.add_placeholder') }}"
                                   class="block w-full rounded-lg border bg-white py-2 pl-3 pr-3 text-sm font-mono text-gray-900 shadow-sm focus:outline-none focus:ring-2 {{ $errors->has('new_number') || $errors->has('number') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/40' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40' }}">
                            <p class="mt-1 text-xs text-gray-400">{{ __('messages.settings.whatsapp.add_hint') }}</p>
                            @error('new_number')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('number')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="self-start shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            {{ __('messages.settings.whatsapp.add_submit') }}
                        </button>
                    </form>
                </div>
            </section>

        </div>

        {{-- ===== KYC / KYB Tab ===== --}}
        <div id="panel-kyckyb" role="tabpanel" @class(['space-y-6', 'hidden' => $activeTab !== 'kyckyb'])>

            {{-- KYC - Personal Identity --}}
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.kyckyb.kyc_title') }}</h2>
                </div>

                <div class="px-5 py-5">
                    @if ($kyc)
                        <dl class="space-y-4">
                            @if (! empty($kyc['full_name']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.full_name_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $kyc['full_name'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyc['date_of_birth']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.date_of_birth_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $kyc['date_of_birth'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyc['id_type']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.id_type_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $idTypeLabels[$kyc['id_type']] ?? $kyc['id_type'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyc['id_number']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.id_number_label') }}</dt>
                                    <dd class="mt-0.5 text-sm font-mono text-gray-900">{{ $kyc['id_number'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyc['address']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.address_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900 whitespace-pre-line">{{ $kyc['address'] }}</dd>
                                </div>
                            @endif
                            @if (isset($kyc['status']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.settings.kyckyb.status_label') }}</dt>
                                    <dd class="mt-1">
                                        @if ($kyc['status'] === 'verified')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                {{ __('messages.settings.kyckyb.status_verified') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                                {{ __('messages.settings.kyckyb.status_pending') }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="text-sm text-gray-400">{{ __('messages.settings.kyckyb.no_data') }}</p>
                    @endif
                </div>
            </section>

            {{-- KYB - Business Profile --}}
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">{{ __('messages.settings.kyckyb.kyb_title') }}</h2>
                </div>

                <div class="px-5 py-5">
                    @if ($kyb)
                        <dl class="space-y-4">
                            @if (! empty($kyb['legal_name']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.legal_name_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $kyb['legal_name'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyb['business_type']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.business_type_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bizTypeLabels[$kyb['business_type']] ?? $kyb['business_type'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyb['registration_number']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.registration_number_label') }}</dt>
                                    <dd class="mt-0.5 text-sm font-mono text-gray-900">{{ $kyb['registration_number'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyb['tax_id']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.tax_id_label') }}</dt>
                                    <dd class="mt-0.5 text-sm font-mono text-gray-900">{{ $kyb['tax_id'] }}</dd>
                                </div>
                            @endif
                            @if (! empty($kyb['address']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.onboarding.address_label') }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900 whitespace-pre-line">{{ $kyb['address'] }}</dd>
                                </div>
                            @endif
                            @if (isset($kyb['status']))
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">{{ __('messages.settings.kyckyb.status_label') }}</dt>
                                    <dd class="mt-1">
                                        @if ($kyb['status'] === 'verified')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                {{ __('messages.settings.kyckyb.status_verified') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                                {{ __('messages.settings.kyckyb.status_pending') }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="text-sm text-gray-400">{{ __('messages.settings.kyckyb.no_data') }}</p>
                    @endif
                </div>
            </section>

        </div>
    </div>

    {{-- Tab switching --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs   = document.querySelectorAll('[data-tab]');
            const panels = document.querySelectorAll('[role="tabpanel"]');

            function activate(key) {
                tabs.forEach(function (t) {
                    const active = t.dataset.tab === key;
                    t.setAttribute('aria-selected', active);
                    t.classList.toggle('bg-white', active);
                    t.classList.toggle('text-gray-900', active);
                    t.classList.toggle('shadow-sm', active);
                    t.classList.toggle('text-gray-500', !active);
                    t.classList.toggle('hover:text-gray-700', !active);
                    t.classList.toggle('hover:bg-white/50', !active);
                });
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.id !== 'panel-' + key);
                });
                // Update URL without reload
                var url = new URL(window.location);
                url.searchParams.set('tab', key);
                history.replaceState(null, '', url);
            }

            tabs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activate(btn.dataset.tab);
                });
            });
        });
    </script>
</x-layouts.app>
