<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalDispatchResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalDispatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostalDispatches extends ListRecords
{
    protected static string $resource = PostalDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
