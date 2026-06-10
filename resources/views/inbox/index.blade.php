<x-layouts.app :title="__('messages.inbox.title')" :full-height="true">

<div class="flex h-full flex-col">
<x-messages-tabs active="customers" />

<div id="inbox-root" class="flex min-h-0 flex-1" data-inbox
     data-thread-url-template="{{ url('/app/inbox/{id}/thread') }}"
     data-reply-url-template="{{ url('/app/inbox/{id}/reply') }}"
     data-assign-url-template="{{ url('/app/inbox/{id}/assign') }}"
     data-status-url-template="{{ url('/app/inbox/{id}/status') }}"
     data-notes-url-template="{{ url('/app/inbox/{id}/notes') }}"
     data-read-url-template="{{ url('/app/inbox/{id}/read') }}"
     data-typing-url-template="{{ url('/app/inbox/{id}/typing') }}"
     data-takeover-url-template="{{ url('/app/inbox/{id}/takeover') }}"
     data-handback-url-template="{{ url('/app/inbox/{id}/handback') }}"
     data-stream-url="{{ url('/app/inbox/stream') }}"
     data-index-url="{{ route('inbox.index') }}"
     data-show-url-template="{{ url('/app/inbox/{id}') }}"
     data-i18n-typing="{{ __('messages.inbox.typing') }}"
     data-i18n-read="{{ __('messages.inbox.read') }}"
     data-csrf="{{ csrf_token() }}"
     data-login-url="{{ route('login') }}"
     data-i18n-error-thread="{{ __('messages.inbox.error_load_thread') }}"
     data-i18n-error-reply="{{ __('messages.inbox.error_send_reply') }}"
     data-i18n-error-assign="{{ __('messages.inbox.error_assign') }}"
     data-i18n-error-status="{{ __('messages.inbox.error_status') }}"
     data-i18n-error-note="{{ __('messages.inbox.error_add_note') }}"
     data-i18n-error-load="{{ __('messages.inbox.error_load_more') }}"
     data-i18n-reply-success="{{ __('messages.inbox.reply_success') }}"
     data-i18n-note-success="{{ __('messages.inbox.note_success') }}"
     data-i18n-assign-success="{{ __('messages.inbox.assign_success') }}"
     data-i18n-unassign-success="{{ __('messages.inbox.unassign_success') }}"
     data-i18n-status-updated="{{ __('messages.inbox.status_updated') }}"
     data-i18n-customer="{{ __('messages.inbox.message_inbound') }}"
     data-i18n-agent="{{ __('messages.inbox.message_outbound') }}"
     data-i18n-note-label="{{ __('messages.inbox.note_label') }}"
     data-i18n-unassigned="{{ __('messages.inbox.unassigned') }}"
     data-i18n-assigned-to="{{ __('messages.inbox.assigned_to') }}"
     data-i18n-take="{{ __('messages.inbox.take') }}"
     data-i18n-unassign="{{ __('messages.inbox.unassign') }}"
     data-i18n-status-open="{{ __('messages.inbox.status_open') }}"
     data-i18n-status-pending="{{ __('messages.inbox.status_pending') }}"
     data-i18n-status-resolved="{{ __('messages.inbox.status_resolved') }}"
     data-i18n-status-closed="{{ __('messages.inbox.status_closed') }}"
     data-i18n-ai-active="{{ __('messages.inbox.ai_active') }}"
     data-i18n-human-active="{{ __('messages.inbox.human_active') }}"
     data-i18n-takeover="{{ __('messages.inbox.takeover') }}"
     data-i18n-handback="{{ __('messages.inbox.handback') }}"
     data-i18n-takeover-success="{{ __('messages.inbox.takeover_success') }}"
     data-i18n-handback-success="{{ __('messages.inbox.handback_success') }}"
     data-i18n-error-handoff="{{ __('messages.inbox.error_handoff') }}">

    {{-- Toast container --}}
    <div id="inbox-toast" class="pointer-events-none fixed right-4 top-4 z-50 space-y-2" aria-live="polite"></div>

    {{-- Left panel: conversation list --}}
    <div id="inbox-list-panel" class="flex w-full flex-col border-r border-gray-200 bg-white lg:w-80 lg:shrink-0">

        {{-- Header --}}
        <div class="shrink-0 border-b border-gray-200 px-4 py-3">
            <h1 class="text-sm font-semibold text-gray-900">{{ __('messages.inbox.title') }}</h1>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.inbox.subtitle') }}</p>
        </div>

        {{-- Filters --}}
        <div class="shrink-0 flex gap-2 border-b border-gray-100 px-4 py-2">
            <select data-inbox-filter="channel"
                    class="flex-1 rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40">
                <option value="">{{ __('messages.inbox.filter_all') }} {{ __('messages.inbox.filter_channel') }}</option>
                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>{{ __('messages.inbox.channel_whatsapp') }}</option>
                <option value="instagram" {{ request('channel') === 'instagram' ? 'selected' : '' }}>{{ __('messages.inbox.channel_instagram') }}</option>
                <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>{{ __('messages.inbox.channel_email') }}</option>
                <option value="webchat" {{ request('channel') === 'webchat' ? 'selected' : '' }}>{{ __('messages.inbox.channel_webchat') }}</option>
            </select>
            <select data-inbox-filter="status"
                    class="flex-1 rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40">
                <option value="">{{ __('messages.inbox.filter_all') }} {{ __('messages.inbox.filter_status') }}</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ __('messages.inbox.status_open') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('messages.inbox.status_pending') }}</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>{{ __('messages.inbox.status_resolved') }}</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ __('messages.inbox.status_closed') }}</option>
            </select>
        </div>

        {{-- Conversation list --}}
        <div id="inbox-conversation-list" class="flex-1 overflow-y-auto">
            @forelse ($conversations as $conv)
                <x-inbox.conversation-item :conversation="$conv" :active="isset($activeId) && ($conv['id'] ?? '') === $activeId" />
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">{{ __('messages.inbox.no_conversations') }}</p>
            @endforelse

            @if (!empty($hasMore))
                <div class="px-4 py-3">
                    <button type="button" data-inbox-load-more data-offset="{{ $nextOffset ?? 0 }}"
                            class="w-full rounded-lg border border-gray-300 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">
                        {{ __('messages.inbox.load_more') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Right panel: thread or empty state --}}
    <div id="inbox-thread-panel" class="hidden flex-1 flex-col bg-gray-50 lg:flex">
        @if (isset($conversation) && !empty($conversation))
            @include('inbox.partials.thread', ['conversation' => $conversation, 'messages' => $threadMessages ?? []])
        @else
            <div class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h2.21a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">{{ __('messages.inbox.empty_state') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
</div>

</x-layouts.app>
