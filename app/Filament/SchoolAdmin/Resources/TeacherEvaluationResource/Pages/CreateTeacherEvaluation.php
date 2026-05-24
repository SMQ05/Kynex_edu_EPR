<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Pages;

use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource;
use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Support\ComputesEvaluationScore;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherEvaluation extends CreateRecord
{
    use ComputesEvaluationScore;

    protected static string $resource = TeacherEvaluationResource::class;

    /** @param  array<string,mixed>  $data  @return array<string,mixed> */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['evaluator_id'] ??= auth()->guard('school_users')->id();

        return $this->withComputedScore($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
