<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelGatePassResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelGatePassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditHostelGatePass extends EditRecord
{
    protected static string $resource = HostelGatePassResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
