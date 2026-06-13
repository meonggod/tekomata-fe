<x-layouts.internal-auth :title="__('messages.internal.auth.forgot_title') . ' · tekomata internal'">
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ __('messages.internal.auth.forgot_title') }}</h1>
    <p class="mt-1.5 text-sm text-gray-600">{{ __('messages.internal.auth.forgot_subtitle') }}</p>

    @if (session('status'))
        <p class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('internal.password.email') }}" class="mt-7 space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('messages.internal.auth.email_label') }}</label>
            <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" autocomplete="email"
                   placeholder="you@tekomata.com"
                   class="mt-1.5 block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
            {{ __('messages.internal.auth.forgot_submit') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <a href="{{ route('internal.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.auth.back_to_login') }}</a>
    </p>
</x-layouts.internal-auth>
