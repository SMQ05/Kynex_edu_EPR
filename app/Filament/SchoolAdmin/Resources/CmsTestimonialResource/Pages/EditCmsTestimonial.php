<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsTestimonialResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsTestimonialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsTestimonial extends EditRecord
{
    protected static string $resource = CmsTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
