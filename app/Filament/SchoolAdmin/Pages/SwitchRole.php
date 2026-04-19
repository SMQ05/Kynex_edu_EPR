<?php

namespace App\Filament\SchoolAdmin\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SwitchRole extends Page
{
    protected string $view = 'filament.school-admin.pages.switch-role';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $title = 'Switch Role';

    protected static bool $shouldRegisterNavigation = false;

    public array $availableRoles = [];

    public ?string $currentRole = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->currentRole = $user->getActiveRoleName();
        $this->availableRoles = $user->roles->pluck('name')->toArray();
    }

    public function switchTo(string $roleName): void
    {
        $user = auth()->user();

        if ($user->switchRole($roleName)) {
            Notification::make()
                ->title("Switched to {$roleName}")
                ->success()
                ->send();

            $this->redirect(route('filament.school-admin.pages.dashboard'));
        } else {
            Notification::make()
                ->title('Role switch failed')
                ->body('You do not have this role assigned.')
                ->danger()
                ->send();
        }
    }
}
