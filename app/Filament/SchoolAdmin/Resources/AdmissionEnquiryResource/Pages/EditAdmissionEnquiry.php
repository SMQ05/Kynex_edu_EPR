<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionEnquiry extends EditRecord
{
    protected static string $resource = AdmissionEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
