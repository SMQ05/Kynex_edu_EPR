<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant\InAppNotification;
use Livewire\Component;

/**
 * NotificationBell — Livewire component for the notification bell icon
 * with unread count badge and dropdown notification list.
 *
 * Renders in the top navigation bar of the school admin panel.
 * Polls every 30 seconds for new notifications (fallback if Echo not configured).
 */
class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var \Illuminate\Support\Collection<int, InAppNotification> */
    public $notifications;

    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->refreshNotifications();
    }

    /**
     * Reload notifications and unread count from the database.
     */
    public function refreshNotifications(): void
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            $this->notifications = collect();
            $this->unreadCount = 0;
            return;
        }

        $this->notifications = InAppNotification::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $this->unreadCount = InAppNotification::forUser($user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): void
    {
        $notification = InAppNotification::find($id);

        if ($notification && $notification->user_id === auth()->guard('school_users')->id()) {
            $notification->markAsRead();
            $this->unreadCount = max(0, $this->unreadCount - 1);

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
        $user = auth()->guard('school_users')->user();

        if ($user) {
            InAppNotification::forUser($user->id)
                ->unread()
                ->update(['read_at' => now()]);

            $this->unreadCount = 0;
            $this->refreshNotifications();
        }
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;

        if ($this->showDropdown) {
            $this->refreshNotifications();
        }
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
