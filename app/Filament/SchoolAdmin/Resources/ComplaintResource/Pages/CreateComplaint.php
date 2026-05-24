<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ComplaintResource\Pages;

use App\Filament\SchoolAdmin\Resources\ComplaintResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComplaint extends CreateRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
