<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LeaveQuotaResource\Pages;

use App\Filament\SchoolAdmin\Resources\LeaveQuotaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveQuota extends CreateRecord
{
    protected static string $resource = LeaveQuotaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
