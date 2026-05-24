<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant\InAppNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * NotificationBell — Livewire component for the notification bell icon
 * with unread count badge and dropdown notification list.
 *
 * Renders in the top navigation bar of the school admin panel.
 * Polls every 30 seconds for new notifications (fallback if Echo not configured).
 *
 * The notification list is exposed as a #[Computed] property rather than a
 * public property. Holding an Eloquent collection in component state forced
 * Livewire to dehydrate/rehydrate 10 tenant models on every poll, which broke
 * with "Error while loading page" app-wide. A computed property re-queries on
 * render and is never serialized into the snapshot.
 *
 * Every DB access is also guarded by tenancy()->initialized so a dropped /
 * mid-session tenant context degrades to an empty bell instead of a 500.
 */
class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->refreshUnreadCount();
    }

    /**
     * The 10 most recent notifications for the current user. Computed so it is
     * re-queried each render and never stored in the Livewire snapshot.
     *
     * @return Collection<int, InAppNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        if (! tenancy()->initialized) {
            return collect();
        }

        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return collect();
        }

        return InAppNotification::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Recompute the unread badge and bust the cached notifications list.
     * Bound to wire:poll and the Echo listener.
     */
    public function refreshNotifications(): void
    {
        unset($this->notifications); // bust the computed-property cache
        $this->refreshUnreadCount();
    }

    private function refreshUnreadCount(): void
    {
        if (! tenancy()->initialized) {
            $this->unreadCount = 0;
            return;
        }

        $user = auth()->guard('school_users')->user();

        $this->unreadCount = $user
            ? InAppNotification::forUser($user->id)->unread()->count()
            : 0;
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $notification = InAppNotification::find($id);

        if ($notification && $notification->user_id === auth()->guard('school_users')->id()) {
            $notification->markAsRead();

            if ($notification->action_url) {
                $this->redirect($notification->action_url);
                return;
            }
        }

        $this->refreshNotifications();
    }

    /**
     * Mark all notifications as read for the current user.
     */
    public function markAllAsRead(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $user = auth()->guard('school_users')->user();

        if ($user) {
            InAppNotification::forUser($user->id)
                ->unread()
                ->update(['read_at' => now()]);
        }

        $this->refreshNotifications();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    /**
     * Laravel Echo listener for real-time updates.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo:notifications,NotificationCreated' => 'refreshNotifications',
        ];
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
