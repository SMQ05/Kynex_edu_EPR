<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\VisitorResource\Pages;
use App\Filament\SchoolAdmin\Resources\VisitorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditVisitor extends EditRecord
{
    protected static string $resource = VisitorResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
