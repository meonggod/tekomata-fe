<x-layouts.app :title="__('messages.errors.unavailable_title') . ' · tekomata'">
    <div class="mx-auto max-w-md py-16 text-center">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.errors.unavailable_title') }}</h1>
        <p class="mt-3 text-gray-600">
            {{ $message ?? __('messages.errors.unavailable_body') }}
        </p>

        @if (! empty($requestId ?? null))
            {{-- Same id as our logs + Slack alert — the code the user quotes to support. --}}
            <div class="mx-auto mt-6 max-w-sm rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-left">
                <p class="text-xs font-medium text-gray-500">{{ __('messages.errors.ref_label') }}</p>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <code class="truncate font-mono text-sm text-gray-900">{{ $requestId }}</code>
                    <button type="button" data-copy="{{ $requestId }}" data-copied-label="{{ __('messages.errors.copied') }}"
                            class="inline-flex shrink-0 items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <span data-copy-label>{{ __('messages.errors.copy') }}</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ __('messages.errors.ref_hint') }}</p>
            </div>
        @endif

        <a href="{{ url()->current() }}"
           class="mt-6 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
            {{ __('messages.errors.try_again') }}
        </a>
    </div>
</x-layouts.app>
