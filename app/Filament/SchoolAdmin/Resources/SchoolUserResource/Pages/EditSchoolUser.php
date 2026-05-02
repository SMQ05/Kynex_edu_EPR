<?php

namespace App\Filament\SchoolAdmin\Resources\SchoolUserResource\Pages;

use App\Filament\SchoolAdmin\Resources\SchoolUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolUser extends EditRecord
{
    protected static string $resource = SchoolUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Edit form: changing the primary role syncs the user's role list
     * to exactly that one role and updates active_role. Stacking extra
     * roles on top is done via the row "Manage Roles" action.
     */
    protected function afterSave(): void
    {
        $primaryRole = $this->form->getRawState()['primary_role'] ?? null;
        if (! $primaryRole) {
            return;
        }

        if (! $this->record->hasRole($primaryRole)) {
            $this->record->assignRole($primaryRole);
        }

        if ($this->record->active_role !== $primaryRole) {
            $this->record->forceFill(['active_role' => $primaryRole])->saveQuietly();
        }
    }
}
