<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\QuestionGroupResource\Pages;

use App\Filament\SchoolAdmin\Resources\QuestionGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestionGroup extends CreateRecord
{
    protected static string $resource = QuestionGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
