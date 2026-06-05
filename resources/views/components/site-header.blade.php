{{--
    Shared top bar for the public pages (landing, login, register) so they all
    carry the same homepage look. `cta` picks the right-hand action:
      - 'sign_in'     → link to login   (default; used on landing & register)
      - 'get_started' → button to register (used on login)
      - 'none'        → wordmark + language switcher only
--}}
@props(['cta' => 'sign_in'])

<div class="flex items-center justify-between">
    <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-gray-900 hover:text-gray-700">tekomata</a>
    <div class="flex items-center gap-4">
        <x-lang-switcher />
        @if ($cta === 'sign_in')
            <a href="{{ route('login') }}"
               class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900">
                {{ __('messages.nav.sign_in') }}
            </a>
        @elseif ($cta === 'get_started')
            <a href="{{ route('register') }}"
               class="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-800">
                {{ __('messages.nav.get_started') }}
            </a>
        @endif
    </div>
</div>
