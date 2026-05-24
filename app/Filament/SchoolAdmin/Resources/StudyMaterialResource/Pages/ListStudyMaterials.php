<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudyMaterialResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudyMaterialResource;
use App\Models\Tenant\StudyMaterial;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudyMaterials extends ListRecords
{
    protected static string $resource = StudyMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('All')];

        foreach (StudyMaterial::CATEGORIES as $value => $label) {
            $tabs[$value] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $q) => $q->where('category', $value))
                ->badge(fn (): int => StudyMaterialResource::getEloquentQuery()->where('category', $value)->count());
        }

        return $tabs;
    }
}
