@props(['label' => null])

<button type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
    {{ $label ?? __('messages.internal.actions.save') }}
</button>
