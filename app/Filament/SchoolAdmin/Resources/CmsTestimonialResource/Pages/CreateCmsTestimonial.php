<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsTestimonialResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsTestimonialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsTestimonial extends CreateRecord
{
    protected static string $resource = CmsTestimonialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
