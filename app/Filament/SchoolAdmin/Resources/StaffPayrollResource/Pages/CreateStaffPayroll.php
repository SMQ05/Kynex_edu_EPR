<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\StaffPayrollResource\Pages;
use App\Filament\SchoolAdmin\Resources\StaffPayrollResource;
use Filament\Resources\Pages\CreateRecord;
class CreateStaffPayroll extends CreateRecord
{
    protected static string $resource = StaffPayrollResource::class;
}
