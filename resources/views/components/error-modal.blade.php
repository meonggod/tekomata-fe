{{--
    Global "something went wrong" modal. Pops up — once, dismissibly — over
    whatever page the user landed back on after an unexpected backend failure
    (5xx / unreachable). It is a notification, not a wall: backdrop, Esc and the
    Dismiss button all close it so the user can carry on. Shows the request id so
    they can quote it to the help desk (the backend already alerted on it).
    Driven by the `api_error` flash set in Controller::apiErrorModal().
--}}
@if (session('api_error'))
    @php($apiError = session('api_error'))
    <div id="app-error-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="alertdialog" aria-modal="true" aria-labelledby="app-error-modal-title" aria-describedby="app-error-modal-body">
        <div class="absolute inset-0 bg-gray-900/40" data-dismiss-modal aria-hidden="true"></div>

        <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m0 3.75h.008M10.34 3.94 1.86 18a1.5 1.5 0 0 0 1.31 2.25h17.66A1.5 1.5 0 0 0 22.14 18L13.66 3.94a1.5 1.5 0 0 0-2.62 0Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 id="app-error-modal-title" class="text-base font-semibold text-gray-900">
                        {{ __('messages.errors.modal_title') }}
                    </h2>
                    <p id="app-error-modal-body" class="mt-1 text-sm leading-relaxed text-gray-600">
                        {{ __('messages.errors.modal_body') }}
                    </p>
                </div>
            </div>

            @if (! empty($apiError['request_id']))
                {{-- The single code the user quotes; resolves to our logs + alert. --}}
                <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                    <p class="text-xs font-medium text-gray-500">{{ __('messages.errors.ref_label') }}</p>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <code class="truncate font-mono text-sm text-gray-900">{{ $apiError['request_id'] }}</code>
                        <button type="button" data-copy="{{ $apiError['request_id'] }}" data-copied-label="{{ __('messages.errors.copied') }}"
                                class="inline-flex shrink-0 items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 17.25v3a1.5 1.5 0 0 1-1.5 1.5h-9a1.5 1.5 0 0 1-1.5-1.5v-9a1.5 1.5 0 0 1 1.5-1.5h3m4.5-3h4.5a1.5 1.5 0 0 1 1.5 1.5v4.5m-9-6v9a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5v-6.75L15.75 2.25h-4.5a1.5 1.5 0 0 0-1.5 1.5Z" />
                            </svg>
                            <span data-copy-label>{{ __('messages.errors.copy') }}</span>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ __('messages.errors.ref_hint') }}</p>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="button" data-dismiss-modal
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                    {{ __('messages.errors.dismiss') }}
                </button>
            </div>
        </div>
    </div>
@endif
