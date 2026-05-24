<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsMenuResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsMenuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsMenu extends CreateRecord
{
    protected static string $resource = CmsMenuResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
