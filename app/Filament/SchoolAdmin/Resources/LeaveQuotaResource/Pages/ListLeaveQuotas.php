<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LeaveQuotaResource\Pages;

use App\Filament\SchoolAdmin\Resources\LeaveQuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveQuotas extends ListRecords
{
    protected static string $resource = LeaveQuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
