<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\DownloadContentResource\Pages;
use App\Models\Tenant\DownloadContent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DownloadContentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_download_center';

    protected static ?string $model = DownloadContent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Download Center';

    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Content')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('content_type_id')
                        ->label('Content type')
                        ->relationship('contentType', 'name')
                        ->searchable()->preload()->nullable()->createOptionForm([
                            TextInput::make('name')->required()->maxLength(255),
                        ]),
                    Select::make('source_type')
                        ->label('Source')
                        ->options(['file' => 'File', 'url' => 'URL', 'video' => 'Video'])
                        ->default('file')->required()->live()->native(false)
                        ->afterStateUpdated(function (string $state, \Filament\Schemas\Components\Utilities\Set $set): void {
                            $set('is_video', $state === 'video');
                        }),
                    FileUpload::make('file_path')
                        ->label('File')->directory('download-center')
                        ->visible(fn (Get $get): bool => $get('source_type') === 'file')
                        ->required(fn (Get $get): bool => $get('source_type') === 'file'),
                    TextInput::make('external_url')
                        ->label('URL')->url()
                        ->visible(fn (Get $get): bool => in_array($get('source_type'), ['url', 'video'], true))
                        ->required(fn (Get $get): bool => in_array($get('source_type'), ['url', 'video'], true)),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
            FormSection::make('Visibility')
                ->columns(2)
                ->schema([
                    Select::make('audience')
                        ->options(DownloadContent::AUDIENCES)
                        ->default('all')->native(false),
                    DatePicker::make('publish_date')->default(now()),
                    Toggle::make('is_shared')->label('Show in Shared Content')->default(false),
                    Toggle::make('is_video')->label('Is a video')->default(false),
                    Toggle::make('is_published')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40)->weight('semibold'),
                TextColumn::make('contentType.name')->label('Type')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('source_type')->label('Source')->badge(),
                TextColumn::make('audience')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DownloadContent::AUDIENCES[$state] ?? $state),
                IconColumn::make('is_shared')->boolean()->label('Shared')->toggleable(),
                IconColumn::make('is_video')->boolean()->label('Video')->toggleable(),
                TextColumn::make('download_count')->label('Downloads')->sortable()->toggleable(),
                IconColumn::make('is_published')->boolean()->label('Published'),
            ])
            ->filters([
                SelectFilter::make('content_type_id')->relationship('contentType', 'name')->label('Type'),
                SelectFilter::make('audience')->options(DownloadContent::AUDIENCES),
                TernaryFilter::make('is_shared')->label('Shared'),
                TernaryFilter::make('is_video')->label('Video'),
            ])
            ->defaultSort('publish_date', 'desc')
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
            'index'  => Pages\ListDownloadContents::route('/'),
            'create' => Pages\CreateDownloadContent::route('/create'),
            'edit'   => Pages\EditDownloadContent::route('/{record}/edit'),
        ];
    }
}
