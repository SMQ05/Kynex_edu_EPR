<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\QuestionGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\QuestionGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionGroup extends EditRecord
{
    protected static string $resource = QuestionGroupResource::class;

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
