<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\DownloadContentResource\Pages;

use App\Filament\SchoolAdmin\Resources\DownloadContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDownloadContents extends ListRecords
{
    protected static string $resource = DownloadContentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /**
     * Content List / Shared Content List / Video List are filtered views of
     * the one table (no duplicate resources).
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Content List'),
            'shared' => Tab::make('Shared Content')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_shared', true))
                ->badge(fn (): int => DownloadContentResource::getEloquentQuery()->where('is_shared', true)->count()),
            'video' => Tab::make('Video List')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_video', true))
                ->badge(fn (): int => DownloadContentResource::getEloquentQuery()->where('is_video', true)->count()),
        ];
    }
}
