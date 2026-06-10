<x-layouts.app :title="__('messages.team.title')" :full-height="true">

<div class="flex h-full flex-col">
<x-messages-tabs active="team" />

<div id="team-root" class="flex min-h-0 flex-1" data-team
     data-thread-url-template="{{ url('/app/team/{id}/thread') }}"
     data-send-url-template="{{ url('/app/team/{id}/messages') }}"
     data-create-url="{{ url('/app/team/conversations') }}"
     data-add-members-url-template="{{ url('/app/team/{id}/members') }}"
     data-stream-url="{{ url('/app/inbox/stream') }}"
     data-index-url="{{ route('team.index') }}"
     data-show-url-template="{{ url('/app/team/{id}') }}"
     data-csrf="{{ csrf_token() }}"
     data-login-url="{{ route('login') }}"
     data-current-user-id="{{ $currentUserId ?? '' }}"
     data-i18n-error-thread="{{ __('messages.team.error_load_thread') }}"
     data-i18n-error-send="{{ __('messages.team.error_send') }}"
     data-i18n-error-create="{{ __('messages.team.error_create') }}"
     data-i18n-error-add-members="{{ __('messages.team.error_add_members') }}"
     data-i18n-reply-success="{{ __('messages.team.reply_success') }}"
     data-i18n-create-success="{{ __('messages.team.create_success') }}"
     data-i18n-add-members-success="{{ __('messages.team.add_members_success') }}"
     data-i18n-badge-direct="{{ __('messages.team.badge_direct') }}"
     data-i18n-badge-group="{{ __('messages.team.badge_group') }}"
     data-i18n-reply-placeholder="{{ __('messages.team.reply_placeholder') }}"
     data-i18n-reply-submit="{{ __('messages.team.reply_submit') }}"
     data-i18n-add-members="{{ __('messages.team.add_members') }}">

    {{-- Toast container --}}
    <div id="team-toast" class="pointer-events-none fixed right-4 top-4 z-50 space-y-2" aria-live="polite"></div>

    {{-- Left panel: conversation list --}}
    <div id="team-list-panel" class="flex w-full flex-col border-r border-gray-200 bg-white lg:w-80 lg:shrink-0">

        {{-- Header --}}
        <div class="shrink-0 border-b border-gray-200 px-4 py-3">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-sm font-semibold text-gray-900">{{ __('messages.team.title') }}</h1>
                    <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.team.subtitle') }}</p>
                </div>
                <button type="button" data-team-new-chat-open
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('messages.team.new_chat') }}
                </button>
            </div>
        </div>

        {{-- Conversation list --}}
        <div id="team-conversation-list" class="flex-1 overflow-y-auto">
            @forelse ($conversations as $conv)
                <x-team.conversation-item :conversation="$conv" :active="isset($activeId) && ($conv['id'] ?? '') === $activeId" />
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">{{ __('messages.team.no_conversations') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Right panel: thread or empty state --}}
    <div id="team-thread-panel" class="hidden flex-1 flex-col bg-gray-50 lg:flex">
        @if (isset($conversation) && !empty($conversation))
            @include('team.partials.thread', ['conversation' => $conversation, 'messages' => $threadMessages ?? [], 'currentUserId' => $currentUserId ?? ''])
        @else
            <div class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">{{ __('messages.team.empty_state') }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- New Chat Modal --}}
    <div id="team-new-chat-modal" class="fixed inset-0 z-50 hidden" aria-modal="true">
        <div class="fixed inset-0 bg-black/40" data-team-modal-backdrop></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.team.new_chat') }}</h3>

                <form data-team-create-form class="mt-4 space-y-4">
                    {{-- Scope radio --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('messages.team.scope_label') }}</label>
                        <div class="mt-1.5 flex gap-4">
                            <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                                <input type="radio" name="scope" value="direct" checked
                                       class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500/40"
                                       data-team-scope-radio>
                                {{ __('messages.team.scope_direct') }}
                            </label>
                            <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                                <input type="radio" name="scope" value="group"
                                       class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500/40"
                                       data-team-scope-radio>
                                {{ __('messages.team.scope_group') }}
                            </label>
                        </div>
                    </div>

                    {{-- Direct fields --}}
                    <div data-team-direct-fields>
                        <label class="block text-sm font-medium text-gray-700">{{ __('messages.team.user_id_label') }}</label>
                        <input type="text" name="user_id"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                               placeholder="{{ __('messages.team.user_id_hint') }}">
                    </div>

                    {{-- Group fields --}}
                    <div data-team-group-fields class="hidden space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('messages.team.group_title_label') }}</label>
                            <input type="text" name="title"
                                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                                   placeholder="{{ __('messages.team.group_title_hint') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('messages.team.members_label') }}</label>
                            <input type="text" name="member_user_ids"
                                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                                   placeholder="{{ __('messages.team.members_hint') }}">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-team-modal-close
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('messages.team.cancel') }}
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            {{ __('messages.team.create_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Members Modal --}}
    <div id="team-add-members-modal" class="fixed inset-0 z-50 hidden" aria-modal="true">
        <div class="fixed inset-0 bg-black/40" data-team-members-backdrop></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.team.add_members') }}</h3>

                <form data-team-add-members-form class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('messages.team.add_members_label') }}</label>
                        <input type="text" name="member_user_ids"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                               placeholder="{{ __('messages.team.add_members_hint') }}">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-team-members-close
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('messages.team.cancel') }}
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            {{ __('messages.team.add_members_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

</x-layouts.app>
