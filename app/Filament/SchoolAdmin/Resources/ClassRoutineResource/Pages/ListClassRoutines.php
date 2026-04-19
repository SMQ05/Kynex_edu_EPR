<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassRoutineResource\Pages;

use App\Filament\SchoolAdmin\Pages\ClassRoutinePlanner;
use App\Filament\SchoolAdmin\Resources\ClassRoutineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassRoutines extends ListRecords
{
    protected static string $resource = ClassRoutineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('planner')
                ->label('Routine Planner')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(ClassRoutinePlanner::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
