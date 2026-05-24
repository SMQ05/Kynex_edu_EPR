<x-filament-panels::page>
    @php
        $conversations = $this->conversations();
        $active        = $this->getActiveConversation();
        $messages      = $active ? $this->messages() : collect();
        $me            = auth()->guard('school_users')->id();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4" style="min-height: 32rem;">
        {{-- Conversation list --}}
        <div class="md:col-span-1 fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Conversations</h3>
            </div>

            @if ($conversations->isEmpty())
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400">
                    No conversations yet. Use "New chat" above to start one.
                </p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($conversations as $conversation)
                        <li>
                            <button type="button"
                                    wire:click="selectConversation('{{ $conversation->id }}')"
                                    @class([
                                        'flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-white/5',
                                        'bg-primary-50 dark:bg-primary-500/10' => $active && $active->id === $conversation->id,
                                    ])>
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                    {{ \Illuminate\Support\Str::of($this->conversationTitle($conversation))->substr(0, 1)->upper() }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $this->conversationTitle($conversation) }}
                                    </span>
                                    @if ($conversation->last_message_at)
                                        <span class="block text-xs text-gray-400">
                                            {{ $conversation->last_message_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Active thread --}}
        <div class="md:col-span-2 lg:col-span-3 fi-section flex flex-col rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            @if (! $active)
                <div class="flex flex-1 items-center justify-center p-8 text-sm text-gray-500 dark:text-gray-400">
                    Select a conversation or start a new chat.
                </div>
            @else
                <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $this->conversationTitle($active) }}
                    </h3>
                </div>

                {{-- Messages --}}
                <div class="flex-1 space-y-3 overflow-y-auto p-4" style="max-height: 26rem;">
                    @forelse ($messages as $message)
                        @php $mine = $message->sender_id === $me; @endphp
                        <div @class(['flex', 'justify-end' => $mine])>
                            <div @class([
                                'max-w-[75%] rounded-2xl px-3.5 py-2 text-sm',
                                'bg-primary-600 text-white' => $mine,
                                'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' => ! $mine,
                            ])>
                                @unless ($mine)
                                    <div class="mb-0.5 text-xs font-semibold opacity-70">
                                        {{ $message->sender?->name ?? 'Unknown' }}
                                    </div>
                                @endunless
                                <div class="whitespace-pre-wrap break-words">{{ $message->body }}</div>
                                <div class="mt-0.5 text-right text-[10px] opacity-60">
                                    {{ $message->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No messages yet. Say hello.</p>
                    @endforelse
                </div>

                {{-- Composer --}}
                <div class="border-t border-gray-100 p-3 dark:border-white/5">
                    <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                        <textarea
                            wire:model="draft"
                            rows="2"
                            placeholder="Type a message…"
                            class="fi-input block w-full resize-none rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>

                        <div class="flex flex-col gap-2">
                            @if ($this->aiEnabled())
                                <button type="button"
                                        wire:click="suggestReply"
                                        wire:loading.attr="disabled"
                                        title="Suggest a reply with AI"
                                        class="fi-btn inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200">
                                    ✨
                                </button>
                            @endif
                            <button type="submit"
                                    class="fi-btn inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
