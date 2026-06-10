@php
    $conv = $conversation ?? [];
    $msgs = $messages ?? [];
    $convId = $conv['id'] ?? '';
    $scope = $conv['scope'] ?? 'direct';
    $title = $conv['title'] ?? __('messages.team.badge_direct');
    $currentUserId = $currentUserId ?? '';
@endphp

<div id="team-thread" class="flex h-full flex-col" data-conversation-id="{{ $convId }}" data-scope="{{ $scope }}">
    {{-- Thread header --}}
    <div class="shrink-0 border-b border-gray-200 bg-white px-4 py-3 sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    {{-- Mobile back button --}}
                    <button type="button" data-team-back
                            class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <h2 class="truncate text-sm font-semibold text-gray-900">{{ $title }}</h2>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                    @php
                        $scopeColor = $scope === 'group'
                            ? 'bg-purple-100 text-purple-700'
                            : 'bg-indigo-100 text-indigo-700';
                        $scopeLabel = $scope === 'group'
                            ? __('messages.team.badge_group')
                            : __('messages.team.badge_direct');
                    @endphp
                    <span class="inline-flex items-center rounded-full {{ $scopeColor }} px-2 py-0.5 text-xs font-medium">
                        {{ $scopeLabel }}
                    </span>
                </div>
            </div>

            {{-- Actions: add members (groups only) --}}
            @if ($scope === 'group')
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" data-team-add-members-open
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        {{ __('messages.team.add_members') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Message list --}}
    <div id="team-messages" class="flex-1 overflow-y-auto px-4 py-4 sm:px-6" data-team-messages>
        @forelse ($msgs as $msg)
            @php
                $body = $msg['body'] ?? '';
                $authorId = $msg['author_user_id'] ?? '';
                $authorName = $msg['author_name'] ?? '';
                $isMine = $authorId === $currentUserId;
                $msgTime = '';
                if (!empty($msg['created_at'])) {
                    try {
                        $msgTime = \Carbon\Carbon::parse($msg['created_at'])->format('M j, H:i');
                    } catch (\Throwable) {
                        $msgTime = $msg['created_at'];
                    }
                }
            @endphp

            @if ($isMine)
                {{-- My message — right aligned, indigo --}}
                <div class="mb-3 flex justify-end">
                    <div class="max-w-xs rounded-lg bg-indigo-600 px-4 py-2.5 sm:max-w-md">
                        <div class="flex items-center gap-2 text-xs text-indigo-200">
                            <span class="font-medium">{{ $authorName }}</span>
                            <span>{{ $msgTime }}</span>
                        </div>
                        <p class="mt-1 text-sm text-white whitespace-pre-wrap">{{ $body }}</p>
                    </div>
                </div>
            @else
                {{-- Other's message — left aligned, gray --}}
                <div class="mb-3 flex justify-start">
                    <div class="max-w-xs rounded-lg bg-gray-100 px-4 py-2.5 sm:max-w-md">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-medium">{{ $authorName }}</span>
                            <span>{{ $msgTime }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">{{ $body }}</p>
                    </div>
                </div>
            @endif
        @empty
            <p class="py-8 text-center text-sm text-gray-400">{{ __('messages.team.empty_state') }}</p>
        @endforelse
    </div>

    {{-- Reply composer --}}
    <div class="shrink-0 border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
        <form data-team-reply-form class="flex gap-2">
            <textarea data-team-reply-body rows="1"
                      class="block flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                      placeholder="{{ __('messages.team.reply_placeholder') }}"></textarea>
            <button type="submit" data-team-reply-submit
                    class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:opacity-50">
                {{ __('messages.team.reply_submit') }}
            </button>
        </form>
    </div>
</div>
