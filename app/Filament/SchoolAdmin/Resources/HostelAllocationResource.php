<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HostelAllocationResource\Pages;
use App\Models\Tenant\HostelAllocation;
use App\Models\Tenant\HostelRoom;
use App\Models\Tenant\Student;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class HostelAllocationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_room_allocations';

    protected static ?string $model = HostelAllocation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'Hostel';

    protected static ?string $navigationLabel = 'Room Allocation';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Allocation Details')->schema([
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => Student::where('status', 'enrolled')
                        ->whereDoesntHave('hostelAllocations', fn ($q) => $q->where('status', 'active'))
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => $s->full_name . ' - ' . $s->admission_number]))
                    ->searchable()
                    ->required(),

                Select::make('room_id')
                    ->label('Room')
                    ->options(fn () => HostelRoom::where('status', 'available')
                        ->with('building', 'roomType')
                        ->get()
                        ->mapWithKeys(fn ($r) => [$r->id => $r->building->name . ' - ' . $r->room_number . ' (' . $r->available_beds . '/' . $r->total_beds . ' beds)']))
                    ->searchable()
                    ->required(),

                TextInput::make('bed_number')
                    ->numeric()
                    ->nullable(),

                DatePicker::make('check_in_date')
                    ->required()
                    ->default(now()),

                DatePicker::make('expected_check_out')
                    ->nullable(),

                TextInput::make('monthly_fee_pkr')
                    ->label('Monthly Fee (PKR)')
                    ->numeric()
                    ->required()
                    ->prefix('PKR')
                    ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                    ->formatStateUsing(fn ($state, $record) => $record ? $record->monthly_fee_paisas / 100 : 0),

                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')->label('Student')->searchable(),
                TextColumn::make('room.building.name')->label('Building'),
                TextColumn::make('room.room_number')->label('Room'),
                TextColumn::make('bed_number')->label('Bed'),
                TextColumn::make('check_in_date')->date()->sortable(),
                TextColumn::make('monthly_fee_paisas')
                    ->label('Fee')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 2)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'checked_out' => 'gray',
                        'transferred' => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'checked_out' => 'Checked Out',
                        'transferred' => 'Transferred',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('checkOut')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'checked_out',
                            'check_out_date' => now(),
                        ]);
                        $record->room?->decrement('occupied_beds');
                        if ($record->room && $record->room->occupied_beds < $record->room->total_beds) {
                            $record->room->update(['status' => 'available']);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelAllocations::route('/'),
            'create' => Pages\CreateHostelAllocation::route('/create'),
            'edit' => Pages\EditHostelAllocation::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['allocated_by'] = auth()->id();
        return $data;
    }
}
