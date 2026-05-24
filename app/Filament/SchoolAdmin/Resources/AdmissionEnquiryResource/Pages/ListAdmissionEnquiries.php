<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\Pages;

use App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionEnquiries extends ListRecords
{
    protected static string $resource = AdmissionEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
