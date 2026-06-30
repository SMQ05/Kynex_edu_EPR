<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\NoticeResource\Pages;

use App\Filament\SchoolAdmin\Resources\NoticeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

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
