@props(['active' => 'customers'])

{{--
    Messages switcher — segmented tabs shared by the two sibling Messages views:
    "Customers" (omnichannel agent inbox) and "Team" (internal team chat). The
    tabs are plain links — each navigates to its own full-height page, so each
    keeps its own split-pane, SSE stream, and JS controller intact. The sidebar
    shows a single "Inbox" item active across both (inbox.* | team.*).
--}}
@php
    $tabs = [
        'customers' => ['label' => __('messages.nav.tab_customers'), 'url' => route('inbox.index')],
        'team' => ['label' => __('messages.nav.tab_team'), 'url' => route('team.index')],
    ];
@endphp

<div class="flex shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 py-2.5 sm:px-6">
    <span class="text-sm font-semibold text-gray-900">{{ __('messages.nav.inbox') }}</span>
    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" role="tablist">
        @foreach ($tabs as $key => $tab)
            <a href="{{ $tab['url'] }}"
               role="tab"
               @if ($active === $key) aria-current="page" aria-selected="true" @endif
               @class([
                   'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                   'bg-white text-indigo-700 shadow-sm' => $active === $key,
                   'text-gray-500 hover:text-gray-800' => $active !== $key,
               ])>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
