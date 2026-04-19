<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CampusResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\Campus;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class CampusResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = Campus::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Campuses';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Campus Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Components\Select::make('campus_owner_user_id')
                        ->label('Campus Head')
                        ->options(
                            SchoolUser::query()
                                ->whereHas('roles', fn ($q) => $q->where('name', 'INSTITUTE_HEAD'))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Components\Textarea::make('address')
                        ->rows(3)
                        ->columnSpanFull(),

                    Components\TextInput::make('city')
                        ->maxLength(100),

                    Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),

                    Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),

                    Components\Toggle::make('is_main_campus')
                        ->label('Main Campus')
                        ->helperText('Only one campus can be the main campus.')
                        ->default(false),

                    Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_main_campus')
                    ->label('Main')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Campus Head')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_main_campus')
                    ->label('Main Campus'),
            ])
            ->actions([
                Action::make('setAsMain')
                    ->label('Set as Main')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Set as Main Campus')
                    ->modalDescription('This will unset any existing main campus and set this one as the main campus.')
                    ->action(function (Campus $record): void {
                        Campus::where('is_main_campus', true)->update(['is_main_campus' => false]);
                        $record->update(['is_main_campus' => true]);
                    })
                    ->visible(fn (Campus $record): bool => ! $record->is_main_campus),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampuses::route('/'),
            'create' => Pages\CreateCampus::route('/create'),
            'edit' => Pages\EditCampus::route('/{record}/edit'),
        ];
    }
}
