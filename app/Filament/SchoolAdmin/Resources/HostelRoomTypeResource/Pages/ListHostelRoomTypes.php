<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelRoomTypeResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelRoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListHostelRoomTypes extends ListRecords
{
    protected static string $resource = HostelRoomTypeResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
