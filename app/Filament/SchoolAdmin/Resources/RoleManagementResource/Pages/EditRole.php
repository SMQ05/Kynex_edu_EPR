<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\RoleManagementResource\Pages;

use App\Filament\SchoolAdmin\Resources\RoleManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->record->is_system),
        ];
    }
}
