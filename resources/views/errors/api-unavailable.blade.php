<x-layouts.app :title="__('messages.errors.unavailable_title') . ' · tekomata'">
    <div class="mx-auto max-w-md py-16 text-center">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.errors.unavailable_title') }}</h1>
        <p class="mt-3 text-gray-600">
            {{ $message ?? __('messages.errors.unavailable_body') }}
        </p>
        <a href="{{ url()->current() }}"
           class="mt-6 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
            {{ __('messages.errors.try_again') }}
        </a>
    </div>
</x-layouts.app>
