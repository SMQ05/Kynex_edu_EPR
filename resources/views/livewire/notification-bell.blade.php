<div class="relative" wire:poll.30s="refreshNotifications">
    {{--
        NOTE (Risk 6): Dropdown uses z-50 which is sufficient for the Filament topbar.
        If Filament modals (z-40 default) overlap, increase to z-[60] here. Test by
        opening the bell dropdown while a Filament modal is visible.
    --}}

    {{-- Bell Icon Button --}}
    <button
        wire:click="toggleDropdown"
        class="relative p-2 text-gray-500 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition"
        aria-label="Notifications"
    >
        <x-heroicon-o-bell class="w-5 h-5" />

        {{-- Unread Badge --}}
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 {{ app()->getLocale() === 'ur' ? 'left-0.5' : 'right-0.5' }} inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    @if ($showDropdown)
        <div class="absolute {{ app()->getLocale() === 'ur' ? 'left-0' : 'right-0' }} mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Notifications
                </h3>
                @if ($unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium"
                    >
                        Mark all as read
                    </button>
                @endif
            </div>

            {{-- Notification List --}}
            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($this->notifications as $notification)
                    <button
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition flex items-start gap-3 {{ $notification->read_at ? 'opacity-60' : '' }}"
                    >
                        {{-- Type Icon --}}
                        <div class="flex-shrink-0 mt-0.5">
                            @switch($notification->type)
                                @case('success')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30">
                                        <x-heroicon-s-check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    </span>
                                    @break
                                @case('warning')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30">
                                        <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                    </span>
                                    @break
                                @case('danger')
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30">
                                        <x-heroicon-s-x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30">
                                        <x-heroicon-s-information-circle class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    </span>
                            @endswitch
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $notification->title }}
                                </p>
                                {{-- Unread indicator --}}
                                @unless ($notification->read_at)
                                    <span class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full"></span>
                                @endunless
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5">
                                {{ $notification->body }}
                            </p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </button>
                @empty
                    <div class="px-4 py-8 text-center">
                        <x-heroicon-o-bell-slash class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600" />
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            No notifications yet
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-2">
                <a
                    href="{{ url('/admin/notifications') }}"
                    class="block text-center text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium py-1"
                >
                    View all notifications
                </a>
            </div>
        </div>
    @endif
</div>
