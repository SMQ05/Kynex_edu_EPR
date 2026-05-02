<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelAllocationResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListHostelAllocations extends ListRecords
{
    protected static string $resource = HostelAllocationResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
