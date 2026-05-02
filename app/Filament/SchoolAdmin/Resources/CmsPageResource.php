<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CmsPageResource\Pages;
use App\Models\Tenant\CmsPage;
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
use Illuminate\Support\Str;

class CmsPageResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static string | \UnitEnum | null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly slug. Auto-generated from title.'),

                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO & Publishing')
                    ->collapsible()
                    ->schema([
                        TextInput::make('meta_title')
                            ->maxLength(255)
                            ->helperText('Custom page title for search engines. Leave empty to use page title.'),

                        TextInput::make('meta_description')
                            ->maxLength(500)
                            ->helperText('Short description for search engines.'),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        DateTimePicker::make('published_at')
                            ->label('Publish Date'),

                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),

                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Updated'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsPages::route('/'),
            'create' => Pages\CreateCmsPage::route('/create'),
            'edit' => Pages\EditCmsPage::route('/{record}/edit'),
        ];
    }
}
