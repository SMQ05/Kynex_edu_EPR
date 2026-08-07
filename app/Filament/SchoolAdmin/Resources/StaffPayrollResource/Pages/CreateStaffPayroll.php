<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StaffPayrollResource\Pages;

use App\Filament\SchoolAdmin\Resources\StaffPayrollResource;
use App\Models\Tenant\StaffPayroll;
use App\Models\Tenant\StaffProfile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateStaffPayroll extends CreateRecord
{
    protected static string $resource = StaffPayrollResource::class;

    /**
     * Guard against duplicate payrolls before the insert hits the database.
     *
     * Throws a ValidationException so Filament renders the message as an
     * inline error on the Month field instead of bubbling up as a 500.
     */
    protected function beforeCreate(): void
    {
        $exists = StaffPayroll::where('staff_profile_id', $this->data['staff_profile_id'])
            ->where('month', $this->data['month'])
            ->where('year', $this->data['year'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.month' => 'A payroll record for this staff member already exists for the selected month and year.',
            ]);
        }
    }

    /**
     * Remap the form's virtual field names to the model's actual column names
     * before the record is inserted into the database.
     *
     * The form uses human-readable keys (basic_salary, allowances, deductions,
     * net_salary) so that dehydrateStateUsing can multiply by 100, but those
     * keys are not in $fillable. We translate them here and also pull
     * school_user_id from the selected StaffProfile.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remap virtual salary fields → *_paisas columns.
        // dehydrateStateUsing already multiplied by 100, so the value is
        // already in paisas — we just need to rename the key.
        $data['basic_salary_paisas'] = (int) ($data['basic_salary'] ?? 0);
        $data['allowances_paisas']   = (int) ($data['allowances']   ?? 0);
        $data['deductions_paisas']   = (int) ($data['deductions']   ?? 0);
        $data['net_salary_paisas']   = (int) ($data['net_salary']   ?? 0);

        // Remove the virtual keys so they don't confuse the model.
        unset($data['basic_salary'], $data['allowances'], $data['deductions'], $data['net_salary']);

        // Populate school_user_id from the linked StaffProfile if not already set.
        if (empty($data['school_user_id']) && ! empty($data['staff_profile_id'])) {
            $profile = StaffProfile::find($data['staff_profile_id']);
            if ($profile) {
                $data['school_user_id'] = $profile->school_user_id;
            }
        }

        return $data;
    }
}
