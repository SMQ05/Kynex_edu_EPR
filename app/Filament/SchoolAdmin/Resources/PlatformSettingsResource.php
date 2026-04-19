<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\OnlineClassPlatformType;
use App\Filament\SchoolAdmin\Resources\PlatformSettingsResource\Pages;
use App\Models\Tenant\OnlineClassPlatform;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformSettingsResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = OnlineClassPlatform::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'Online Classes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static ?string $modelLabel = 'Platform';

    protected static ?string $pluralModelLabel = 'Platforms';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. School Zoom Account'),

                        Select::make('platform_type')
                            ->options(OnlineClassPlatformType::class)
                            ->required()
                            ->reactive()
                            ->label('Platform Type'),

                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])->columns(2),

                Section::make('Platform Configuration')
                    ->description('Enter the API keys and configuration for this platform. Keys depend on platform type.')
                    ->schema([
                        KeyValue::make('config')
                            ->label('Configuration')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText(function (callable $get): string {
                                $type = $get('platform_type');
                                return match ($type) {
                                    'zoom' => 'Required keys: api_key, api_secret, account_id',
                                    'google_meet' => 'Required keys: oauth_token (placeholder for OAuth flow)',
                                    'jitsi' => 'Required keys: server_url (default: meet.jit.si)',
                                    'bigbluebutton' => 'Required keys: server_url, secret_key',
                                    'teams' => 'Required keys: tenant_id, client_id, client_secret',
                                    default => 'Select a platform type to see required configuration keys.',
                                };
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('platform_type')
                    ->badge()
                    ->color(fn (OnlineClassPlatformType $state): string => match ($state) {
                        OnlineClassPlatformType::Zoom => 'info',
                        OnlineClassPlatformType::GoogleMeet => 'success',
                        OnlineClassPlatformType::Jitsi => 'warning',
                        OnlineClassPlatformType::BigBlueButton => 'primary',
                        OnlineClassPlatformType::Teams => 'purple',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('online_classes_count')
                    ->counts('onlineClasses')
                    ->label('Classes'),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformSettings::route('/'),
            'create' => Pages\CreatePlatformSettings::route('/create'),
            'edit' => Pages\EditPlatformSettings::route('/{record}/edit'),
        ];
    }
}
