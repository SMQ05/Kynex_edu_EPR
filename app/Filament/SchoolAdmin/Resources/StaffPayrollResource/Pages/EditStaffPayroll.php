<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StaffPayrollResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffPayrollResource;
use App\Models\Tenant\StaffProfile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffPayroll extends EditRecord
{
    protected static string $resource = StaffPayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * Remap the form's virtual field names to the model's actual column names
     * before the record is updated in the database.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remap virtual salary fields → *_paisas columns.
        $data['basic_salary_paisas'] = (int) ($data['basic_salary'] ?? 0);
        $data['allowances_paisas']   = (int) ($data['allowances']   ?? 0);
        $data['deductions_paisas']   = (int) ($data['deductions']   ?? 0);
        $data['net_salary_paisas']   = (int) ($data['net_salary']   ?? 0);

        // Remove the virtual keys so they don't confuse the model.
        unset($data['basic_salary'], $data['allowances'], $data['deductions'], $data['net_salary']);

        // Keep school_user_id in sync if the staff member was changed.
        if (! empty($data['staff_profile_id'])) {
            $profile = StaffProfile::find($data['staff_profile_id']);
            if ($profile) {
                $data['school_user_id'] = $profile->school_user_id;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
