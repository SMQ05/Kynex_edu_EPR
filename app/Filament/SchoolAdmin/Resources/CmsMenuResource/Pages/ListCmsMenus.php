<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsMenuResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsMenus extends ListRecords
{
    protected static string $resource = CmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
