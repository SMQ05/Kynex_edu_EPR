<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CustomFieldResource\Pages;

use App\Filament\SchoolAdmin\Resources\CustomFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomFields extends ListRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
