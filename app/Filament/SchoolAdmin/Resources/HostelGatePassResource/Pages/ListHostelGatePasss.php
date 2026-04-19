<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelGatePassResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelGatePassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListHostelGatePasss extends ListRecords
{
    protected static string $resource = HostelGatePassResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
