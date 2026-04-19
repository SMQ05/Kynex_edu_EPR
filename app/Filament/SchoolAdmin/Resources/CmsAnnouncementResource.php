<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CmsAnnouncementResource\Pages;
use App\Models\Tenant\CmsAnnouncement;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CmsAnnouncementResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_announcement_board';

    protected static ?string $model = CmsAnnouncement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Announcements';

    protected static string | \UnitEnum | null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Announcement Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Publishing')
                    ->collapsible()
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),

                        DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now())
                            ->helperText('When to start showing the announcement.'),

                        DateTimePicker::make('expires_at')
                            ->label('Expiry Date')
                            ->helperText('When to stop showing it. Leave empty for no expiry.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),

                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Published'),

                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Expires')
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsAnnouncements::route('/'),
            'create' => Pages\CreateCmsAnnouncement::route('/create'),
            'edit' => Pages\EditCmsAnnouncement::route('/{record}/edit'),
        ];
    }
}
