<?php

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\OnlineClass;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingOnlineClassesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Online Classes';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OnlineClass::query()
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '>=', now())
                    ->orderBy('scheduled_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Title'),

                TextColumn::make('schoolClass.name')
                    ->label('Class'),

                TextColumn::make('subject.name')
                    ->label('Subject'),

                TextColumn::make('teacher.name')
                    ->label('Teacher'),

                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->label('Scheduled At'),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min'),

                TextColumn::make('platform.name')
                    ->label('Platform'),
            ])
            ->paginated(false);
    }
}
