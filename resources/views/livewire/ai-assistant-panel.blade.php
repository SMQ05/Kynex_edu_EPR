<div class="fixed bottom-6 right-6 z-50" wire:key="ai-assistant-panel">
    {{-- Floating launcher button --}}
    @unless($open)
        <button type="button"
                wire:click="toggle"
                class="rounded-full shadow-lg bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 flex items-center gap-2 transition">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
            <span class="text-sm font-semibold">Ask AI</span>
        </button>
    @endunless

    {{-- Open panel --}}
    @if($open)
        <div class="w-[380px] max-w-[calc(100vw-2rem)] h-[560px] max-h-[calc(100vh-3rem)] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 flex flex-col overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 bg-indigo-600 text-white">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">AI</div>
                    <div>
                        <div class="text-sm font-semibold">KynexEdu Assistant</div>
                        <div class="text-[10px] opacity-80">{{ str_replace('_', ' ', $activeRole) }} mode</div>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="startNewConversation" title="New chat" class="p-1.5 rounded hover:bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                        </svg>
                    </button>
                    <button wire:click="toggle" title="Close" class="p-1.5 rounded hover:bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Conversation --}}
            <div id="ai-chat-scroll" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-gray-50 dark:bg-gray-950">
                @if(empty($messages))
                    <div class="text-center text-xs text-gray-500 dark:text-gray-400 mt-3 mb-4 px-2">
                        Ask anything school-related. Try one of these to start:
                    </div>
                    <div class="space-y-2">
                        @foreach($starters as $s)
                            <button wire:click="useStarter(@js($s))"
                                    @disabled($busy)
                                    class="block w-full text-left text-xs px-3 py-2 rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                {{ $s }}
                            </button>
                        @endforeach
                    </div>
                @else
                    @foreach($messages as $m)
                        <div class="flex {{ $m['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] px-3 py-2 rounded-2xl text-sm whitespace-pre-wrap break-words
                                {{ $m['role'] === 'user'
                                    ? 'bg-indigo-600 text-white rounded-br-sm'
                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 ring-1 ring-gray-200 dark:ring-gray-700 rounded-bl-sm' }}">
                                {{ $m['content'] }}
                                @if(! empty($m['ts']))
                                    <div class="text-[9px] opacity-60 mt-1">{{ $m['ts'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                @if($busy)
                    <div class="flex justify-start">
                        <div class="px-3 py-2 rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700 rounded-bl-sm">
                            <div class="flex gap-1">
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0.15s"></span>
                                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0.3s"></span>
                            </div>
                        </div>
                    </div>
                @endif

                @if($error)
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/30 ring-1 ring-red-200 dark:ring-red-800 px-3 py-2 text-xs text-red-800 dark:text-red-200">
                        {{ $error }}
                    </div>
                @endif
            </div>

            {{-- Input --}}
            <form wire:submit.prevent="ask" class="p-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-end gap-2">
                    <textarea
                        wire:model="input"
                        @disabled($busy)
                        rows="2"
                        placeholder="Type your question…"
                        class="flex-1 resize-none rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm text-sm focus:ring-1 focus:ring-indigo-500"
                    ></textarea>
                    <button type="submit"
                            @disabled($busy)
                            class="rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-3 py-2 text-sm font-semibold">
                        Send
                    </button>
                </div>
                <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500 text-center">
                    AI may make mistakes — verify important details.
                </div>
            </form>
        </div>

        <script>
            // Scroll the conversation to the latest message after each Livewire update.
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', () => {
                    const el = document.getElementById('ai-chat-scroll');
                    if (el) { el.scrollTop = el.scrollHeight; }
                });
            });
        </script>
    @endif
</div>
