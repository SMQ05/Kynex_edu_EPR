<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\PostalReceiveResource\Pages;

use App\Filament\SchoolAdmin\Resources\PostalReceiveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostalReceives extends ListRecords
{
    protected static string $resource = PostalReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
