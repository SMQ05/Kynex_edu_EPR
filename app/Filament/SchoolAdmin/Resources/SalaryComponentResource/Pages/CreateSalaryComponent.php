<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\SalaryComponentResource\Pages;
use App\Filament\SchoolAdmin\Resources\SalaryComponentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateSalaryComponent extends CreateRecord
{
    protected static string $resource = SalaryComponentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
