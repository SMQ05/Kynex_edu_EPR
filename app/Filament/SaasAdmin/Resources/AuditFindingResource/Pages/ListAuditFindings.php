<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\AuditFindingResource\Pages;

use App\Filament\SaasAdmin\Resources\AuditFindingResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditFindings extends ListRecords
{
    protected static string $resource = AuditFindingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
