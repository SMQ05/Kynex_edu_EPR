<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\NoticeResource\Pages;

use App\Filament\SchoolAdmin\Resources\NoticeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotice extends CreateRecord
{
    protected static string $resource = NoticeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
