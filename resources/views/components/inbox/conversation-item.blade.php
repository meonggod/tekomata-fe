@props(['conversation', 'active' => false])

@php
    $c = $conversation;
    $id = $c['id'] ?? '';
    $channel = $c['channel'] ?? 'unknown';
    $status = $c['status'] ?? 'open';
    $title = $c['title'] ?? $c['contact_name'] ?? __('messages.inbox.thread_title');
    $snippet = $c['last_message_preview'] ?? '';
    $assignee = $c['assignee_name'] ?? null;
    $kind = $c['kind'] ?? 'human';
    $updatedAt = $c['updated_at'] ?? '';

    $channelIcons = [
        'whatsapp'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />',
        'instagram' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />',
        'email'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />',
        'webchat'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />',
    ];

    $channelLabels = [
        'whatsapp'  => __('messages.inbox.channel_whatsapp'),
        'instagram' => __('messages.inbox.channel_instagram'),
        'email'     => __('messages.inbox.channel_email'),
        'webchat'   => __('messages.inbox.channel_webchat'),
    ];

    $statusColors = [
        'open'     => 'bg-green-100 text-green-700',
        'pending'  => 'bg-yellow-100 text-yellow-700',
        'resolved' => 'bg-blue-100 text-blue-700',
        'closed'   => 'bg-gray-100 text-gray-600',
    ];

    $statusLabels = [
        'open'     => __('messages.inbox.status_open'),
        'pending'  => __('messages.inbox.status_pending'),
        'resolved' => __('messages.inbox.status_resolved'),
        'closed'   => __('messages.inbox.status_closed'),
    ];

    $channelIcon = $channelIcons[$channel] ?? $channelIcons['webchat'];
    $channelLabel = $channelLabels[$channel] ?? $channel;
    $statusColor = $statusColors[$status] ?? $statusColors['open'];
    $statusLabel = $statusLabels[$status] ?? $status;

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

<a href="{{ route('inbox.show', $id) }}"
   data-conversation-id="{{ $id }}"
   class="flex gap-3 rounded-lg px-3 py-3 transition-colors cursor-pointer
          {{ $active ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50' }}">
    {{-- Channel icon --}}
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.75" aria-hidden="true">
            {!! $channelIcon !!}
        </svg>
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2">
            <p class="truncate text-sm font-medium text-gray-900">{{ $title }}</p>
            @if ($timeDisplay)
                <span class="shrink-0 text-xs text-gray-400">{{ $timeDisplay }}</span>
            @endif
        </div>
        <p class="mt-0.5 truncate text-xs text-gray-500">{{ $snippet }}</p>
        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                {{ $statusLabel }}
            </span>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                {{ $channelLabel }}
            </span>
            @if ($kind === 'ai')
                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                    {{ __('messages.inbox.ai_badge') }}
                </span>
            @endif
            @if ($assignee)
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-600">
                    {{ $assignee }}
                </span>
            @endif
        </div>
    </div>
</a>
