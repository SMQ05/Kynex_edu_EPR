<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LeaveQuotaResource\Pages;

use App\Filament\SchoolAdmin\Resources\LeaveQuotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaveQuota extends EditRecord
{
    protected static string $resource = LeaveQuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
