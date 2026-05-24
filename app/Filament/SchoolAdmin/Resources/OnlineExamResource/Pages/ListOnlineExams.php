<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\OnlineExamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnlineExams extends ListRecords
{
    protected static string $resource = OnlineExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
