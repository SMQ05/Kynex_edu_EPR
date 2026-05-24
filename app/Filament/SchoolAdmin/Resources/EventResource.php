<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\EventResource\Pages;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_events';

    protected static ?string $model = Event::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Event')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull()
                        ->hintActions([
                            AiActions::draftInto('description', [
                                'instruction'   => 'an engaging description for this school event that informs and invites the audience',
                                'contextFields' => ['title' => 'Event title', 'location' => 'Location', 'audience' => 'Audience'],
                                'feature'       => 'event_description_draft',
                                'channel'       => 'notice',
                            ]),
                            AiActions::refineInto('description'),
                        ]),

                    DateTimePicker::make('start_at')
                        ->label('Starts')
                        ->required()
                        ->default(now()->addDay()->startOfHour())
                        ->seconds(false),

                    DateTimePicker::make('end_at')
                        ->label('Ends')
                        ->seconds(false)
                        ->afterOrEqual('start_at')
                        ->nullable(),

                    Toggle::make('all_day')->label('All-day event'),

                    TextInput::make('location')->maxLength(255),
                ]),

            Section::make('Audience & Display')
                ->columns(3)
                ->schema([
                    Select::make('audience')
                        ->options(Event::AUDIENCES)
                        ->default('all')
                        ->native(false)
                        ->required(),

                    ColorPicker::make('color')
                        ->default('#1a56db')
                        ->helperText('Shown on the calendar.'),

                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true)
                        ->helperText('Unpublished events are hidden from the calendar.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('color')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn (): string => '●')
                    ->extraAttributes(fn (Event $record): array => ['style' => 'color:' . ($record->color ?? '#1a56db')])
                    ->toggleable(),
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('start_at')->label('Starts')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('end_at')->label('Ends')->dateTime('d M Y, H:i')->toggleable()->placeholder('—'),
                TextColumn::make('location')->toggleable()->placeholder('—'),
                TextColumn::make('audience')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Event::AUDIENCES[$state] ?? (string) $state),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('audience')->options(Event::AUDIENCES),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->defaultSort('start_at', 'desc')
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
            'index'  => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit'   => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
