<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\QuestionGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\QuestionGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestionGroups extends ListRecords
{
    protected static string $resource = QuestionGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
