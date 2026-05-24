<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Pages;

use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource;
use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Support\ComputesEvaluationScore;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeacherEvaluation extends EditRecord
{
    use ComputesEvaluationScore;

    protected static string $resource = TeacherEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** @param  array<string,mixed>  $data  @return array<string,mixed> */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->withComputedScore($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
