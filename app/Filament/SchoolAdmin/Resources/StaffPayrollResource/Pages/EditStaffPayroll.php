<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\StaffPayrollResource\Pages;
use App\Filament\SchoolAdmin\Resources\StaffPayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditStaffPayroll extends EditRecord
{
    protected static string $resource = StaffPayrollResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
