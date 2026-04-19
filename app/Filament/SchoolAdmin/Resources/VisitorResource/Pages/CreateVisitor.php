<?php
declare(strict_types=1);
namespace App\Filament\SchoolAdmin\Resources\VisitorResource\Pages;
use App\Filament\SchoolAdmin\Resources\VisitorResource;
use Filament\Resources\Pages\CreateRecord;
class CreateVisitor extends CreateRecord
{
    protected static string $resource = VisitorResource::class;
}
