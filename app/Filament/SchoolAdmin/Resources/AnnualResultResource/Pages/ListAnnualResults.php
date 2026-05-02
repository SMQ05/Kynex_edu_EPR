<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AnnualResultResource\Pages;

use App\Filament\SchoolAdmin\Resources\AnnualResultResource;
use Filament\Resources\Pages\ListRecords;

class ListAnnualResults extends ListRecords
{
    protected static string $resource = AnnualResultResource::class;
}
