{{--
    Async catalog import — upload + live job tracker + conflict review + history.
    Progressive enhancement: the panel is inert without JS (the file form would
    still POST), but the live tracker / review / history are driven by app.js
    (initCatalogImport). All routes + localized strings are handed to JS via the
    JSON config below so no UI string is hard-coded in script.
--}}
@php
    $jobUrl = fn (string $name) => route($name, ['job' => '__JOB__']);
    $importConfig = [
        'csrf' => csrf_token(),
        'routes' => [
            'enqueue' => route('catalog.import.store'),
            'history' => route('catalog.import.history'),
            'template' => route('catalog.import.template'),
            'stream' => $jobUrl('catalog.import.stream'),
            'staged' => $jobUrl('catalog.import.staged'),
            'autoApply' => $jobUrl('catalog.import.auto-apply'),
            'decisions' => $jobUrl('catalog.import.decisions'),
            'apply' => $jobUrl('catalog.import.apply'),
            'retry' => $jobUrl('catalog.import.retry'),
            'discard' => $jobUrl('catalog.import.discard'),
        ],
        'i18n' => __('messages.catalog.import'),
    ];
@endphp

<div data-import class="mt-4">
    <script type="application/json" data-import-config>@json($importConfig)</script>

    {{-- Upload panel — toggled by the Import button in the header. --}}
    <div data-import-panel hidden
         class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.catalog.import.panel_title') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.catalog.import.panel_subtitle') }}</p>
            </div>
            <button type="button" data-import-close
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="{{ __('messages.catalog.import.close_button') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form data-import-form class="space-y-4 px-5 py-5">
            <div>
                <label for="catalog_file" class="block text-sm font-medium text-gray-700">{{ __('messages.catalog.import.file_label') }}</label>
                <input type="file" id="catalog_file" name="catalog_file" accept=".xlsx,.csv,.txt" required
                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-gray-500">{{ __('messages.catalog.import.format_hint') }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ __('messages.catalog.import.columns_hint') }}</p>
                <a href="{{ route('catalog.import.template') }}"
                   class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-500">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    {{ __('messages.catalog.import.download_template') }}
                </a>
            </div>

            <label class="flex items-start gap-2.5">
                <input type="checkbox" name="auto_apply" value="1"
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm">
                    <span class="font-medium text-gray-700">{{ __('messages.catalog.import.auto_apply_label') }}</span>
                    <span class="block text-xs text-gray-500">{{ __('messages.catalog.import.auto_apply_hint') }}</span>
                </span>
            </label>

            <p data-import-form-error hidden class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>

            <div class="flex justify-end">
                <button type="submit" data-import-submit
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60">
                    {{ __('messages.catalog.import.upload_button') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Live job tracker — one card per upload, stacks for concurrent jobs. --}}
    <div data-import-jobs class="mt-3 space-y-2 empty:mt-0"></div>

    {{-- Review drawer — populated by JS when Review/Retry is clicked. --}}
    <div data-import-review hidden></div>

    {{-- Import history — collapsible. --}}
    <div class="mt-4">
        <button type="button" data-import-history-toggle
                class="flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900">
            <svg data-import-history-chevron class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
            {{ __('messages.catalog.import.history_toggle') }}
        </button>
        <div data-import-history hidden class="mt-3"></div>
    </div>
</div>
