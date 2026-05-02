<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\Teacher;

use App\Models\Tenant\ClassSubject;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeacherClassesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'My Classes';

    public static function canView(): bool
    {
        $user = auth()->guard('school_users')->user();

        return $user?->hasRole('TEACHER') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassSubject::query()
                    ->with(['schoolClass', 'section', 'subject', 'academicYear'])
                    ->where('teacher_id', auth()->guard('school_users')->id())
            )
            ->columns([
                TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                TextColumn::make('section.name')->label('Section')->placeholder('All sections')->sortable(),
                TextColumn::make('subject.name')->label('Subject')->sortable()->searchable(),
                TextColumn::make('academicYear.name')->label('Year')->toggleable(),
                IconColumn::make('is_optional')->label('Optional')->boolean(),
            ])
            ->emptyStateHeading('No classes yet')
            ->emptyStateDescription('Once an admin assigns you to a class subject, it will show up here.')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
