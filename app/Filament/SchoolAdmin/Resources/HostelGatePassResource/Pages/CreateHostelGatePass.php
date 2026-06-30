<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\HostelGatePassResource\Pages;
use App\Filament\SchoolAdmin\Resources\HostelGatePassResource;
use Filament\Resources\Pages\CreateRecord;
class CreateHostelGatePass extends CreateRecord
{
    protected static string $resource = HostelGatePassResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
