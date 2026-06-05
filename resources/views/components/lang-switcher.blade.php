{{-- Language switcher. Options come from config/locales.php. --}}
<div class="inline-flex items-center gap-1 text-sm">
    @foreach (config('locales.supported') as $code => $label)
        @if ($code === app()->getLocale())
            <span class="rounded-md bg-gray-900 px-2.5 py-1 font-medium text-white"
                  aria-current="true" title="{{ $label }}">{{ strtoupper($code) }}</span>
        @else
            <a href="{{ route('locale.switch', $code) }}"
               class="rounded-md px-2.5 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900"
               title="{{ $label }}">{{ strtoupper($code) }}</a>
        @endif
    @endforeach
</div>
