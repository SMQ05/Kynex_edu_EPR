<?php

namespace App\Filament\SaasAdmin\Resources\ApprovalRequestResource\Pages;

use App\Filament\SaasAdmin\Resources\ApprovalRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApprovalRequest extends EditRecord
{
    protected static string $resource = ApprovalRequestResource::class;

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
}
