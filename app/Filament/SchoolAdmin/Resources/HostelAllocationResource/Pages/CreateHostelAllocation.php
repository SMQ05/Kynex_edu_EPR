<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelAllocationResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelAllocationResource;
use Filament\Resources\Pages\CreateRecord;
class CreateHostelAllocation extends CreateRecord
{
    protected static string $resource = HostelAllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
