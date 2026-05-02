<?php

namespace App\Filament\SchoolAdmin\Resources\CertificateTemplateResource\Pages;

use App\Filament\SchoolAdmin\Resources\CertificateTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCertificateTemplate extends EditRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
