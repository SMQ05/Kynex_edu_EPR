<?php

namespace App\Filament\SchoolAdmin\Resources\CertificateTemplateResource\Pages;

use App\Filament\SchoolAdmin\Resources\CertificateTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCertificateTemplates extends ListRecords
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
