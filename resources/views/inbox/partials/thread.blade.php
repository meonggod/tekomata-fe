@php
    $conv = $conversation ?? [];
    $msgs = $messages ?? [];
    $convId = $conv['id'] ?? '';
    $channel = $conv['channel'] ?? 'unknown';
    $status = $conv['status'] ?? 'open';
    $title = $conv['title'] ?? $conv['contact_name'] ?? __('messages.inbox.thread_title');
    $kind = $conv['kind'] ?? 'human';
    $assignee = $conv['assignee_name'] ?? null;
    $assigneeId = $conv['assignee_user_id'] ?? null;

    $channelLabels = [
        'whatsapp'  => __('messages.inbox.channel_whatsapp'),
        'instagram' => __('messages.inbox.channel_instagram'),
        'email'     => __('messages.inbox.channel_email'),
        'webchat'   => __('messages.inbox.channel_webchat'),
    ];

    $statusLabels = [
        'open'     => __('messages.inbox.status_open'),
        'pending'  => __('messages.inbox.status_pending'),
        'resolved' => __('messages.inbox.status_resolved'),
        'closed'   => __('messages.inbox.status_closed'),
    ];

    $statusColors = [
        'open'     => 'bg-green-100 text-green-700',
        'pending'  => 'bg-yellow-100 text-yellow-700',
        'resolved' => 'bg-blue-100 text-blue-700',
        'closed'   => 'bg-gray-100 text-gray-600',
    ];
@endphp

<div id="inbox-thread" class="flex h-full flex-col" data-conversation-id="{{ $convId }}" data-channel="{{ $channel }}">
    {{-- Thread header --}}
    <div class="shrink-0 border-b border-gray-200 bg-white px-4 py-3 sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    {{-- Mobile back button --}}
                    <button type="button" data-inbox-back
                            class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <h2 class="truncate text-sm font-semibold text-gray-900">{{ $title }}</h2>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }} px-2 py-0.5 text-xs font-medium"
                          data-inbox-status-chip>
                        {{ $statusLabels[$status] ?? $status }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                        {{ $channelLabels[$channel] ?? $channel }}
                    </span>
                    @if ($kind === 'ai')
                        <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                            {{ __('messages.inbox.ai_badge') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 items-center gap-2">
                {{-- Assign / Take --}}
                @if ($assignee)
                    <span class="hidden text-xs text-gray-500 sm:inline" data-inbox-assignee-label>
                        {{ __('messages.inbox.assigned_to', ['name' => $assignee]) }}
                    </span>
                    <button type="button" data-inbox-unassign
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        {{ __('messages.inbox.unassign') }}
                    </button>
                @else
                    <span class="hidden text-xs text-gray-400 sm:inline" data-inbox-assignee-label>
                        {{ __('messages.inbox.unassigned') }}
                    </span>
                    <button type="button" data-inbox-take
                            class="rounded-lg border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                        {{ __('messages.inbox.take') }}
                    </button>
                @endif

                {{-- Status dropdown --}}
                <select data-inbox-status-select
                        class="rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40">
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>{{ __('messages.inbox.status_open') }}</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ __('messages.inbox.status_pending') }}</option>
                    <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>{{ __('messages.inbox.status_resolved') }}</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>{{ __('messages.inbox.status_closed') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Message list --}}
    <div id="inbox-messages" class="flex-1 overflow-y-auto px-4 py-4 sm:px-6" data-inbox-messages>
        @forelse ($msgs as $msg)
            @php
                $direction = $msg['direction'] ?? 'inbound';
                $body = $msg['body'] ?? '';
                $author = $msg['author_name'] ?? ($direction === 'inbound' ? __('messages.inbox.message_inbound') : __('messages.inbox.message_outbound'));
                $msgTime = '';
                if (!empty($msg['created_at'])) {
                    try {
                        $msgTime = \Carbon\Carbon::parse($msg['created_at'])->format('M j, H:i');
                    } catch (\Throwable) {
                        $msgTime = $msg['created_at'];
                    }
                }
            @endphp

            @if ($direction === 'internal')
                {{-- Internal note --}}
                <div class="mb-3 mx-auto max-w-lg rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-2.5">
                    <div class="flex items-center gap-2 text-xs text-yellow-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span class="font-medium">{{ __('messages.inbox.note_label') }}</span>
                        <span class="ml-auto">{{ $author }} &middot; {{ $msgTime }}</span>
                    </div>
                    <p class="mt-1 text-sm text-yellow-800 whitespace-pre-wrap">{{ $body }}</p>
                </div>
            @elseif ($direction === 'inbound')
                {{-- Inbound (customer) — left aligned --}}
                <div class="mb-3 flex justify-start">
                    <div class="max-w-xs rounded-lg bg-gray-100 px-4 py-2.5 sm:max-w-md">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-medium">{{ $author }}</span>
                            <span>{{ $msgTime }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">{{ $body }}</p>
                    </div>
                </div>
            @else
                {{-- Outbound (agent) — right aligned --}}
                <div class="mb-3 flex justify-end">
                    <div class="max-w-xs rounded-lg bg-indigo-600 px-4 py-2.5 sm:max-w-md">
                        <div class="flex items-center gap-2 text-xs text-indigo-200">
                            <span class="font-medium">{{ $author }}</span>
                            <span>{{ $msgTime }}</span>
                        </div>
                        <p class="mt-1 text-sm text-white whitespace-pre-wrap">{{ $body }}</p>
                    </div>
                </div>
            @endif
        @empty
            <p class="py-8 text-center text-sm text-gray-400">{{ __('messages.inbox.empty_state') }}</p>
        @endforelse
    </div>

    {{-- Reply composer --}}
    <div class="shrink-0 border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
        {{-- Note toggle --}}
        <div class="mb-2 flex items-center gap-3">
            <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer">
                <input type="checkbox" data-inbox-note-toggle
                       class="h-3.5 w-3.5 rounded border-gray-300 text-yellow-500 focus:ring-yellow-500/40">
                <span>{{ __('messages.inbox.note_toggle') }}</span>
            </label>
            @if ($channel === 'whatsapp')
                <span class="text-xs text-amber-600">{{ __('messages.inbox.whatsapp_window_hint') }}</span>
            @endif
        </div>

        <form data-inbox-reply-form class="flex gap-2">
            <textarea data-inbox-reply-body rows="1"
                      class="block flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                      placeholder="{{ __('messages.inbox.reply_placeholder') }}"></textarea>
            <button type="submit" data-inbox-reply-submit
                    class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:opacity-50">
                {{ __('messages.inbox.reply_submit') }}
            </button>
        </form>
    </div>
</div>
