<x-layouts.internal-auth :title="__('messages.internal.auth.set_title') . ' · tekomata internal'">
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ __('messages.internal.auth.set_title') }}</h1>
    <p class="mt-1.5 text-sm text-gray-600">{{ __('messages.internal.auth.set_subtitle') }}</p>

    @error('token')
        <p class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('internal.password.update') }}" class="mt-7 space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('messages.internal.auth.new_password_label') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password" placeholder="••••••••"
                   class="mt-1.5 block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2 @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/40 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/40 @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('messages.internal.auth.confirm_password_label') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="••••••••"
                   class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
            {{ __('messages.internal.auth.set_submit') }}
        </button>
    </form>
</x-layouts.internal-auth>
