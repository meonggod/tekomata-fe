<x-layouts.internal :title="'CS review · tekomata internal'">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.internal.cs.title') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('messages.internal.cs.subtitle') }}</p>
    </div>

    @error('form')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('review')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Review queue -----------------------------------------------------}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.cs.queue.title') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.internal.cs.queue.help') }}</p>
            </header>

            @if (empty($questions))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.internal.cs.queue.empty') }}</p>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($questions as $q)
                        <li class="px-5 py-3">
                            <p class="text-sm text-gray-900">{{ $q['question'] ?? '—' }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 font-medium uppercase tracking-wide text-gray-600">{{ $q['surface'] ?? 'homepage' }}</span>
                                @if (! ($q['answered'] ?? true))
                                    <span class="rounded bg-red-50 px-1.5 py-0.5 font-medium text-red-600">{{ __('messages.internal.cs.queue.unanswered') }}</span>
                                @else
                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 font-medium text-amber-700">{{ __('messages.internal.cs.queue.low_confidence') }}</span>
                                @endif
                                @if (! empty($q['company_id']))
                                    <span class="font-mono">{{ __('messages.internal.cs.queue.company') }}: {{ \Illuminate\Support\Str::limit($q['company_id'], 12) }}</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('internal.cs.review', $q['id'] ?? '') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.cs.queue.mark_reviewed') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Knowledge base ---------------------------------------------------}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <header class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('messages.internal.cs.kb.title') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('messages.internal.cs.kb.help') }}</p>
            </header>

            <details class="border-b border-gray-100 px-5 py-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('messages.internal.cs.kb.add') }}</summary>
                <form method="POST" action="{{ route('internal.cs.knowledge.store') }}" class="mt-3 space-y-2">
                    @csrf
                    <x-internal.field name="question" :label="__('messages.internal.cs.kb.col_question')" />
                    <div>
                        <label for="answer" class="block text-xs font-medium text-gray-600">{{ __('messages.internal.cs.kb.col_answer') }}</label>
                        <textarea id="answer" name="answer" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">{{ old('answer') }}</textarea>
                    </div>
                    <x-internal.save-button :label="__('messages.internal.cs.kb.add')" />
                </form>
            </details>

            @if (empty($entries))
                <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('messages.internal.cs.kb.empty') }}</p>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($entries as $e)
                        <li class="px-5 py-3">
                            <details>
                                <summary class="cursor-pointer text-sm font-medium text-gray-900">{{ $e['question'] ?? '—' }}</summary>
                                <form method="POST" action="{{ route('internal.cs.knowledge.update', $e['id'] ?? '') }}" class="mt-3 space-y-2">
                                    @csrf @method('PUT')
                                    <x-internal.field name="question" :label="__('messages.internal.cs.kb.col_question')" :value="$e['question'] ?? ''" />
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">{{ __('messages.internal.cs.kb.col_answer') }}</label>
                                        <textarea name="answer" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">{{ $e['answer'] ?? '' }}</textarea>
                                    </div>
                                    <x-internal.save-button />
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.internal>
