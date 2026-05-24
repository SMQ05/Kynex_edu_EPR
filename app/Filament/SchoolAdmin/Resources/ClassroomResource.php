<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ClassroomResource\Pages;
use App\Models\Tenant\Classroom;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassroomResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_classes';

    protected static ?string $model = Classroom::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Class Rooms';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Room')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('code')->maxLength(40),
                    Select::make('room_type')->options(Classroom::TYPES)->default('classroom')->native(false),
                    TextInput::make('capacity')->numeric()->minValue(0),
                    TextInput::make('building')->maxLength(255),
                    TextInput::make('floor')->maxLength(40),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->placeholder('—')->toggleable(),
                TextColumn::make('room_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Classroom::TYPES[$state] ?? $state),
                TextColumn::make('capacity')->numeric()->placeholder('—'),
                TextColumn::make('building')->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('room_type')->options(Classroom::TYPES),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit'   => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}
