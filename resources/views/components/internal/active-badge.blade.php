@props(['active' => false])

<span @class([
    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-emerald-50 text-emerald-700' => $active,
    'bg-gray-100 text-gray-500' => ! $active,
])>{{ $active ? __('messages.internal.actions.active') : __('messages.internal.actions.inactive') }}</span>
