<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\OnlineClassResource\Pages;
use App\Filament\SchoolAdmin\Resources\OnlineClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOnlineClass extends EditRecord
{
    protected static string $resource = OnlineClassResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
