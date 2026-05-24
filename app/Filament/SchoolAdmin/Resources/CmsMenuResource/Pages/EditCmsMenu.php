<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsMenuResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsMenuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsMenu extends EditRecord
{
    protected static string $resource = CmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
