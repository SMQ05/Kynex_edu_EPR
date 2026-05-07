<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ExamResource\Pages;

use App\Filament\SchoolAdmin\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    public function getView(): string
    {
        if (! $this->userCanViewMarks()) {
            return 'filament.school-admin.pages.forbidden';
        }
        return parent::getView();
    }

    protected function getHeaderActions(): array
    {
        if (! $this->userCanViewMarks()) {
            return [];
        }
        return [
            Actions\CreateAction::make(),
        ];
    }

    private function userCanViewMarks(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'SCHOOL_ADMIN'])) {
            return true;
        }
        try {
            return $user->hasPermissionTo('view_marks');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }
}
