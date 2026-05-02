<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * RoleSwitcher — Livewire component for switching the active role
 * of the currently authenticated school user.
 */
class RoleSwitcher extends Component
{
    public string $activeRole = '';

    public array $availableRoles = [];

    public function mount(): void
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return;
        }

        $this->availableRoles = $user->roles->pluck('name', 'name')->toArray();
        $this->activeRole = $user->getActiveRoleName() ?? '';
    }

    public function switchRole(string $role): void
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return;
        }

        if ($user->switchRole($role)) {
            $this->activeRole = $role;

            Notification::make()
                ->title("Switched to {$role}")
                ->success()
                ->send();

            $this->redirect(url('/admin'), navigate: true);
        } else {
            Notification::make()
                ->title('Role switch failed')
                ->body('You do not have the selected role.')
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.role-switcher');
    }
}
