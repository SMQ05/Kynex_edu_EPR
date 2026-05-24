<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\StudentGroupResource\Pages;
use App\Filament\SchoolAdmin\Resources\StudentGroupResource\RelationManagers\MembersRelationManager;
use App\Models\Tenant\StudentGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentGroupResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';

    protected static ?string $model = StudentGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?string $navigationLabel = 'Student Groups';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Group')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('type')->options(StudentGroup::TYPES)->default('general')->native(false),
                    ColorPicker::make('color')->nullable(),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StudentGroup::TYPES[$state] ?? $state),
                TextColumn::make('members_count')->counts('members')->label('Members'),
            ])
            ->filters([
                SelectFilter::make('type')->options(StudentGroup::TYPES),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [MembersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentGroups::route('/'),
            'create' => Pages\CreateStudentGroup::route('/create'),
            'edit'   => Pages\EditStudentGroup::route('/{record}/edit'),
        ];
    }
}
