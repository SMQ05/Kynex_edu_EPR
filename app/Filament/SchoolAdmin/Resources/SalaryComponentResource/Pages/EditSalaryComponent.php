<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\SalaryComponentResource\Pages;
use App\Filament\SchoolAdmin\Resources\SalaryComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSalaryComponent extends EditRecord
{
    protected static string $resource = SalaryComponentResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
