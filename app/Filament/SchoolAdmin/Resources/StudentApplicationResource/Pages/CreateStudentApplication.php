<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentApplicationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateStudentApplication extends CreateRecord
{
    protected static string $resource = StudentApplicationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['public_token'] = Str::random(40);
        $data['status'] = $data['status'] ?? 'submitted';

        // Force-bind to the actor's own campus when they are campus-scoped
        // (i.e. not INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD). This mirrors the
        // same guard in CreateStudent and ensures the new record is always
        // visible in the list, which filters by campus_id for scoped users.
        $user = auth('school_users')->user();
        if ($user && ! $user->hasAnyRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']) && $user->campus_id) {
            $data['campus_id'] = $user->campus_id;
        }

        return $data;
    }
}
