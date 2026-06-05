<x-layouts.app :title="'tekomata — ' . __('messages.landing.footer.tagline')">
    <x-slot:header>
        <x-site-header cta="sign_in" />
    </x-slot:header>

    {{-- Hero --}}
    <section class="mx-auto max-w-2xl py-16 text-center sm:py-24">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
            {{ __('messages.landing.hero.eyebrow') }}
        </p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
            {{ __('messages.landing.hero.title') }}
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg text-gray-600">
            {{ __('messages.landing.hero.subtitle') }}
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="{{ route('register') }}"
               class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                {{ __('messages.landing.hero.cta_primary') }}
            </a>
            <a href="{{ route('login') }}"
               class="rounded-md px-5 py-2.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                {{ __('messages.landing.hero.cta_secondary') }}
            </a>
        </div>
    </section>

    {{-- Features: what you can ask --}}
    <section class="border-t border-gray-200 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ __('messages.landing.features.heading') }}
            </h2>
            <p class="mt-3 text-gray-600">{{ __('messages.landing.features.subheading') }}</p>
        </div>
        <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (__('messages.landing.features.items') as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-base font-semibold text-gray-900">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Why tekomata --}}
    <section class="border-t border-gray-200 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ __('messages.landing.why.heading') }}
            </h2>
        </div>
        <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-8 sm:grid-cols-3">
            @foreach (__('messages.landing.why.items') as $item)
                <div class="text-center">
                    <h3 class="text-base font-semibold text-gray-900">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-t border-gray-200 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ __('messages.landing.how.heading') }}
            </h2>
        </div>
        <ol class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-6 sm:grid-cols-3">
            @foreach (__('messages.landing.how.steps') as $step)
                <li class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-base font-semibold text-gray-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Pricing teaser --}}
    <section class="border-t border-gray-200 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                {{ __('messages.landing.pricing.heading') }}
            </h2>
            <p class="mt-4 text-gray-600">{{ __('messages.landing.pricing.body') }}</p>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="border-t border-gray-200 py-16">
        <div class="mx-auto max-w-xl rounded-2xl bg-gray-900 px-8 py-12 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-white">
                {{ __('messages.landing.cta.heading') }}
            </h2>
            <p class="mt-3 text-gray-300">{{ __('messages.landing.cta.body') }}</p>
            <a href="{{ route('register') }}"
               class="mt-6 inline-flex rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-100">
                {{ __('messages.landing.cta.button') }}
            </a>
        </div>
    </section>

    <x-site-footer />
</x-layouts.app>
