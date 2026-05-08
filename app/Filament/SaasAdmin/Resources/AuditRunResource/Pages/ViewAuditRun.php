<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\AuditRunResource\Pages;

use App\Filament\SaasAdmin\Resources\AuditRunResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditRun extends ViewRecord
{
    protected static string $resource = AuditRunResource::class;
}
