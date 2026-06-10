@props(['conversation', 'active' => false])

@php
    $c = $conversation;
    $id = $c['id'] ?? '';
    $scope = $c['scope'] ?? 'direct';
    $title = $c['title'] ?? __('messages.team.badge_direct');
    $snippet = $c['last_message_preview'] ?? $c['last_message_body'] ?? '';
    $updatedAt = $c['updated_at'] ?? '';

    $scopeLabel = $scope === 'group'
        ? __('messages.team.badge_group')
        : __('messages.team.badge_direct');

    $scopeColor = $scope === 'group'
        ? 'bg-purple-100 text-purple-700'
        : 'bg-indigo-100 text-indigo-700';

    // Format timestamp
    $timeDisplay = '';
    if ($updatedAt) {
        try {
            $timeDisplay = \Carbon\Carbon::parse($updatedAt)->diffForHumans(short: true);
        } catch (\Throwable) {
            $timeDisplay = $updatedAt;
        }
    }
@endphp

<a href="{{ route('team.show', $id) }}"
   data-conversation-id="{{ $id }}"
   data-scope="{{ $scope }}"
   class="flex gap-3 rounded-lg px-3 py-3 transition-colors cursor-pointer
          {{ $active ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
    {{-- Avatar --}}
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $scope === 'group' ? 'bg-purple-100' : 'bg-indigo-100' }}">
        @if ($scope === 'group')
            <svg class="h-4 w-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
        @else
            <svg class="h-4 w-4 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2">
            <p class="truncate text-sm font-medium text-gray-900">{{ $title }}</p>
            @if ($timeDisplay)
                <span class="shrink-0 text-xs text-gray-400" data-conv-time>{{ $timeDisplay }}</span>
            @endif
        </div>
        <p class="mt-0.5 truncate text-xs text-gray-500" data-conv-snippet>{{ $snippet }}</p>
        <div class="mt-1.5 flex items-center gap-1.5">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $scopeColor }}">
                {{ $scopeLabel }}
            </span>
        </div>
    </div>
</a>
