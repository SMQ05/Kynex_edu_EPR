<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ModuleToggleResource\Pages;
use App\Models\Tenant\ModuleToggle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Module Manager — per-school feature flags (Infix "Module Manager").
 * Complements SaaS plan flags. ModuleToggle::isEnabled('key') is the
 * runtime gate; call it where you want to hide an optional module.
 */
class ModuleToggleResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_modules';

    protected static string $rbacWritePermission = 'manage_school_settings';

    protected static ?string $model = ModuleToggle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Module Manager';

    protected static ?string $modelLabel = 'Module';

    protected static ?int $navigationSort = 51;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Module')
                ->columns(2)
                ->schema([
                    TextInput::make('module_key')
                        ->label('Module key')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Lowercase identifier, e.g. "chat", "library".')
                        ->disabledOn('edit'),
                    TextInput::make('label')->maxLength(255),
                    Toggle::make('enabled')->label('Enabled')->default(true),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Module')
                    ->formatStateUsing(fn (?string $state, ModuleToggle $r): string => $state ?: ModuleToggle::KNOWN_MODULES[$r->module_key] ?? $r->module_key)
                    ->searchable(['label', 'module_key'])
                    ->sortable(),
                TextColumn::make('module_key')->label('Key')->badge()->color('gray')->toggleable(),
                IconColumn::make('enabled')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime('d M Y, H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('enabled'),
            ])
            ->defaultSort('label')
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
            'index'  => Pages\ListModuleToggles::route('/'),
            'create' => Pages\CreateModuleToggle::route('/create'),
            'edit'   => Pages\EditModuleToggle::route('/{record}/edit'),
        ];
    }
}
