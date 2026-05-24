<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsTestimonialResource\Pages;

use App\Filament\SchoolAdmin\Resources\CmsTestimonialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsTestimonials extends ListRecords
{
    protected static string $resource = CmsTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
