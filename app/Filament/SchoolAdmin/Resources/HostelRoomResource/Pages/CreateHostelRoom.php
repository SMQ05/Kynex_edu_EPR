<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelRoomResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelRoomResource;
use Filament\Resources\Pages\CreateRecord;
class CreateHostelRoom extends CreateRecord
{
    protected static string $resource = HostelRoomResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
