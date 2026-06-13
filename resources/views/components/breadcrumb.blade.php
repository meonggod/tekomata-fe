@props(['items' => []])

<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-gray-500">
        @foreach ($items as $index => $item)
            @php $last = $index === array_key_last($items); @endphp
            <li class="flex items-center gap-1">
                @if (! $last)
                    {{-- A crumb is a link only when it carries a `url`; label-only
                         crumbs (e.g. a non-navigable section header) render as text. --}}
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="transition-colors hover:text-gray-900">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                    <svg class="h-3.5 w-3.5 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                @else
                    <span class="font-medium text-gray-900" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
