<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HostelGatePassResource\Pages;
use App\Models\Tenant\HostelAllocation;
use App\Models\Tenant\HostelGatePass;
use App\Models\Tenant\Student;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class HostelGatePassResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_gate_passes';

    protected static ?string $model = HostelGatePass::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    protected static string | \UnitEnum | null $navigationGroup = 'Hostel';

    protected static ?string $navigationLabel = 'Gate Passes';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Gate Pass Details')->schema([
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => Student::whereHas('hostelAllocations', fn ($q) => $q->where('status', 'active'))
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => $s->full_name . ' - ' . $s->admission_number]))
                    ->searchable()
                    ->required(),

                Textarea::make('purpose')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                DateTimePicker::make('out_date_time')
                    ->required()
                    ->default(now()),

                DateTimePicker::make('expected_return_date_time')
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')->label('Student')->searchable(['first_name', 'last_name']),
                TextColumn::make('purpose')->limit(40),
                TextColumn::make('out_date_time')->label('Out Time')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('expected_return_date_time')->label('Expected Return')->dateTime('d M Y H:i'),
                TextColumn::make('actual_return_date_time')->label('Returned At')->dateTime('d M Y H:i'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'out' => 'primary',
                        'returned' => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'out' => 'Out',
                        'returned' => 'Returned',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->guard('school_users')->id(),
                        ]);

                        // Notify parent via notification service
                        try {
                            app(\App\Services\NotificationService::class)->dispatch(
                                'hostel.gate_pass_approved',
                                [['type' => 'parent', 'student_id' => $record->student_id]],
                                [
                                    'student_name' => $record->student?->full_name,
                                    'purpose' => $record->purpose,
                                    'out_date' => $record->out_date_time?->format('d M Y H:i'),
                                ]
                            );
                            $record->update(['parent_notified_at' => now()]);
                        } catch (\Throwable) {
                            // Silently continue if notification fails
                        }
                    }),
                Action::make('markOut')
                    ->label('Mark Out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->action(fn ($record) => $record->update(['status' => 'out'])),
                Action::make('markReturned')
                    ->label('Mark Returned')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['out', 'overdue']))
                    ->action(fn ($record) => $record->update([
                        'status' => 'returned',
                        'actual_return_date_time' => now(),
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelGatePasss::route('/'),
            'create' => Pages\CreateHostelGatePass::route('/create'),
            'edit' => Pages\EditHostelGatePass::route('/{record}/edit'),
        ];
    }
}
