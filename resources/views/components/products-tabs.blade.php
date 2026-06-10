@props(['active' => 'products'])

{{--
    Products switcher — segmented tabs shared by the three sibling product-domain
    views: "Products" (the product list + import), "Catalog" (read-only browse),
    and "Categories" (category CRUD). Tabs are plain links to each index page, so
    each keeps its own controller and layout intact. The sidebar shows a single
    "Products" item active across all three (products.* | catalog.* | categories.*).

    Placed inside each page's content container (these are normal scrolling pages,
    not the full-height split-pane used by x-messages-tabs).
--}}
@php
    $tabs = [
        'products' => ['label' => __('messages.nav.products'), 'url' => route('products.index')],
        'catalog' => ['label' => __('messages.nav.catalog_import'), 'url' => route('catalog.import')],
        'categories' => ['label' => __('messages.nav.categories'), 'url' => route('categories.index')],
    ];
@endphp

<div class="mb-5 inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" role="tablist">
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
