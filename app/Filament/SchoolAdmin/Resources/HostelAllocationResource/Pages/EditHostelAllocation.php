<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelAllocationResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditHostelAllocation extends EditRecord
{
    protected static string $resource = HostelAllocationResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
