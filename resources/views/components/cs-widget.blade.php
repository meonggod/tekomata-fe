@props([
    // 'homepage' (public visitor, shows a sign-up CTA) or 'in_app' (signed-in owner).
    'surface' => 'homepage',
])

{{--
    CS feature-assistant widget — a floating launcher + chat panel that asks the
    tekomata-owned assistant about features/pricing. Progressive enhancement:
    server-rendered shell, driven by initCsWidget() in resources/js/app.js. The
    in-app surface attaches the session JWT server-side (via /cs/ask); the
    homepage surface asks anonymously and nudges toward register.
--}}
<div id="cs-widget" data-cs-widget
     data-cs-surface="{{ $surface }}"
     data-cs-ask-url="{{ route('cs.ask') }}"
     data-cs-error-text="{{ __('messages.cs.error') }}"
     @if ($surface === 'homepage') data-cs-register-url="{{ route('register') }}" @endif
     class="fixed bottom-5 right-5 z-40 flex flex-col items-end">

    {{-- Panel --}}
    <div data-cs-panel hidden
         class="mb-3 flex h-[28rem] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-900 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-white">{{ __('messages.cs.title') }}</p>
                <p class="text-xs text-gray-400">{{ __('messages.cs.subtitle') }}</p>
            </div>
            <button type="button" data-cs-close class="rounded-md p-1 text-gray-400 hover:text-white" aria-label="{{ __('messages.cs.close') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div data-cs-messages class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
            <div class="flex">
                <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-gray-100 px-3.5 py-2 text-sm text-gray-800">
                    {{ __('messages.cs.greeting') }}
                </div>
            </div>
        </div>

        {{-- Sign-up CTA (homepage only) --}}
        @if ($surface === 'homepage')
            <div data-cs-cta class="border-t border-gray-100 bg-indigo-50 px-4 py-2.5 text-center text-xs text-indigo-800">
                {{ __('messages.cs.cta_text') }}
                <a href="{{ route('register') }}" class="font-semibold underline hover:text-indigo-600">{{ __('messages.cs.cta_link') }}</a>
            </div>
        @endif

        {{-- Composer --}}
        <form data-cs-form class="flex items-center gap-2 border-t border-gray-100 px-3 py-3">
            <input type="text" data-cs-input autocomplete="off"
                   placeholder="{{ __('messages.cs.placeholder') }}"
                   class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
            <button type="submit" data-cs-send
                    class="shrink-0 rounded-lg bg-indigo-600 p-2 text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 disabled:opacity-50">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </button>
        </form>
    </div>

    {{-- Launcher --}}
    <button type="button" data-cs-toggle
            class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
            aria-label="{{ __('messages.cs.open') }}">
        <svg data-cs-icon-open class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
        </svg>
        <svg data-cs-icon-close hidden class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>
</div>
