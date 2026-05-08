<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\AuditRunResource\Pages;

use App\Filament\SaasAdmin\Resources\AuditRunResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditRuns extends ListRecords
{
    protected static string $resource = AuditRunResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
