<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\VisitorResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\Visitor;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class VisitorResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_visitors';

    protected static ?string $model = Visitor::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Visitors';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Visitor Information')->schema([
                TextInput::make('visitor_name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('cnic')
                    ->label('CNIC')
                    ->maxLength(15)
                    ->placeholder('XXXXX-XXXXXXX-X'),

                TextInput::make('organization')
                    ->maxLength(255),

                Textarea::make('purpose')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Select::make('school_user_to_meet_id')
                    ->label('Person to Meet')
                    ->relationship('personToMeet', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('whom_to_meet')
                    ->label('Person to Meet (Manual)')
                    ->maxLength(255)
                    ->helperText('Use if person is not in the system'),

                TextInput::make('vehicle_number')
                    ->maxLength(20),

                TextInput::make('badge_number')
                    ->maxLength(20),

                DateTimePicker::make('check_in_time')
                    ->default(now())
                    ->required(),

                Select::make('campus_id')
                    ->relationship('campus', 'name')
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor_name')->searchable()->sortable(),
                TextColumn::make('phone'),
                TextColumn::make('purpose')->limit(30),
                TextColumn::make('whom_to_meet')
                    ->label('Meeting')
                    ->getStateUsing(fn ($record) => $record->personToMeet?->name ?? $record->whom_to_meet ?? '-'),
                TextColumn::make('check_in_time')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('check_out_time')->dateTime('d M Y H:i'),
                TextColumn::make('badge_number'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'checked_in' => 'success',
                        'checked_out' => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'checked_in' => 'Checked In',
                        'checked_out' => 'Checked Out',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Today Only')
                    ->query(fn ($query) => $query->whereDate('check_in_time', today()))
                    ->default(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('checkOut')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'checked_in')
                    ->action(fn ($record) => $record->update([
                        'status' => 'checked_out',
                        'check_out_time' => now(),
                    ])),
            ])
            ->defaultSort('check_in_time', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitors::route('/'),
            'create' => Pages\CreateVisitor::route('/create'),
            'edit' => Pages\EditVisitor::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        return $data;
    }
}
