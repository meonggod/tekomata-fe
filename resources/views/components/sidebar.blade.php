@inject('tokens', 'App\Services\Tekomata\TokenStore')

@php
    $activeCompany = $tokens->activeCompany();

    $navItems = [
        [
            'label' => __('messages.nav.dashboard'),
            'route' => 'dashboard',
            'match' => 'dashboard',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
        ],
        [
            'label' => __('messages.nav.inbox'),
            'route' => 'inbox.index',
            'match' => 'inbox.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h2.21a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />',
        ],
        [
            'label' => __('messages.nav.team_chat'),
            'route' => 'team.index',
            'match' => 'team.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />',
        ],
        [
            'label' => __('messages.nav.products'),
            'route' => 'products.index',
            'match' => 'products.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
        ],
        [
            'label' => __('messages.nav.catalog_import'),
            'route' => 'catalog.import',
            'match' => 'catalog.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />',
        ],
        [
            'label' => __('messages.nav.categories'),
            'route' => 'categories.index',
            'match' => 'categories.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />',
        ],
        [
            'label' => __('messages.nav.warehouses'),
            'route' => 'warehouses.index',
            'match' => 'warehouses.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />',
        ],
        [
            'label' => __('messages.nav.currencies'),
            'route' => 'currencies.index',
            'match' => 'currencies.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        ],
        [
            'label' => __('messages.nav.settings'),
            'route' => 'settings.show',
            'match' => 'settings.*',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        ],
    ];
@endphp

<aside id="app-sidebar"
       class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0">

    {{-- Brand --}}
    <div class="flex h-14 shrink-0 items-center border-b border-gray-200 px-5">
        <a href="{{ route('dashboard') }}" class="text-base font-bold tracking-tight text-gray-900">
            tekomata
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Main">
        <ul class="space-y-0.5">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <li>
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                              {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="h-4 w-4 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400' }}"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            {!! $item['icon'] !!}
                        </svg>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Bottom: active company + lang switcher + sign out --}}
    <div class="shrink-0 space-y-2 border-t border-gray-200 px-3 py-3">
        @if ($activeCompany)
            @php
                $companyName = $activeCompany['name'] ?? '';
                $companyName = trim(preg_replace("/\s*'s\s+company\s*$/i", '', $companyName));
            @endphp
            <p class="truncate px-3 text-xs font-medium text-gray-500">{{ $companyName }}</p>
        @endif
        <div class="flex items-center justify-between px-1">
            <x-lang-switcher />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md px-2 py-1 text-sm text-gray-500 hover:text-gray-900">
                    {{ __('messages.nav.sign_out') }}
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Mobile backdrop --}}
<div id="sidebar-overlay"
     class="fixed inset-0 z-20 hidden bg-black/40 lg:hidden"
     aria-hidden="true"></div>
