<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelRoomResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListHostelRooms extends ListRecords
{
    protected static string $resource = HostelRoomResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
