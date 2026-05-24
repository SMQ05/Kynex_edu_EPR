<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmissionEnquiry extends CreateRecord
{
    protected static string $resource = AdmissionEnquiryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
