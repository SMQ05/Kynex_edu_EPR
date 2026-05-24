<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ComplaintResource\Pages;

use App\Filament\SchoolAdmin\Resources\ComplaintResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
