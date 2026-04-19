<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\StaffAttendanceStatus;
use App\Filament\SchoolAdmin\Resources\StaffAttendanceResource\Pages;
use App\Models\Tenant\StaffAttendanceRecord;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker as FilterDatePicker;

class StaffAttendanceResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_staff_attendance';

    protected static ?string $model = StaffAttendanceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Attendance';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Attendance Record';

    protected static ?string $pluralModelLabel = 'Staff Attendance';

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attendance Details')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([
                        Select::make('school_user_id')
                            ->label('Staff Member')
                            ->relationship('schoolUser', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        DatePicker::make('date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->default(today()),

                        Select::make('status')
                            ->options(StaffAttendanceStatus::class)
                            ->required()
                            ->native(false)
                            ->default(StaffAttendanceStatus::Present->value),

                        TimePicker::make('check_in_time')
                            ->label('Check In')
                            ->seconds(false),

                        TimePicker::make('check_out_time')
                            ->label('Check Out')
                            ->seconds(false),

                        TextInput::make('overtime_minutes')
                            ->label('Overtime (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('min'),

                        Textarea::make('remarks')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolUser.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StaffAttendanceStatus $state) => $state->color())
                    ->sortable(),

                TextColumn::make('check_in_time')
                    ->label('In')
                    ->placeholder('—'),

                TextColumn::make('check_out_time')
                    ->label('Out')
                    ->placeholder('—'),

                TextColumn::make('overtime_minutes')
                    ->label('OT')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . ' min' : '—')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('marker.name')
                    ->label('Marked By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(StaffAttendanceStatus::class),

                Filter::make('date_range')
                    ->form([
                        FilterDatePicker::make('from')
                            ->label('From Date')
                            ->native(false),
                        FilterDatePicker::make('until')
                            ->label('Until Date')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From: ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until: ' . $data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Action::make('bulkMark')
                    ->label('Mark Today\'s Attendance')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->form(function () {
                        // Dynamically list all staff with default Present
                        $staff = \App\Models\Tenant\StaffProfile::with('schoolUser')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->school_user_id => $s->schoolUser?->name ?? 'Unknown']);

                        return [
                            DatePicker::make('attendance_date')
                                ->label('Date')
                                ->required()
                                ->native(false)
                                ->default(today()),

                            Select::make('default_status')
                                ->label('Default Status for All')
                                ->options(StaffAttendanceStatus::class)
                                ->default(StaffAttendanceStatus::Present->value)
                                ->required()
                                ->native(false)
                                ->helperText('All staff will be marked with this status. Edit individual records to change.'),
                        ];
                    })
                    ->action(function (array $data) {
                        $date          = $data['attendance_date'];
                        $defaultStatus = $data['default_status'];
                        $markedBy      = auth()->id();

                        $staffList = \App\Models\Tenant\StaffProfile::all();
                        $created   = 0;
                        $skipped   = 0;

                        foreach ($staffList as $staff) {
                            $exists = StaffAttendanceRecord::where('school_user_id', $staff->school_user_id)
                                ->whereDate('date', $date)
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                continue;
                            }

                            StaffAttendanceRecord::create([
                                'school_user_id' => $staff->school_user_id,
                                'date'           => $date,
                                'status'         => $defaultStatus,
                                'marked_by'      => $markedBy,
                            ]);
                            $created++;
                        }

                        $msg = "{$created} records created.";
                        if ($skipped) {
                            $msg .= " {$skipped} already existed.";
                        }

                        Notification::make()->title($msg)->success()->send();
                    }),
            ])
            ->actions([
                EditAction::make(),

                Action::make('markPresent')
                    ->label('Present')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (StaffAttendanceRecord $record) => $record->status !== StaffAttendanceStatus::Present)
                    ->action(function (StaffAttendanceRecord $record) {
                        $record->update(['status' => StaffAttendanceStatus::Present]);
                        Notification::make()->title('Marked as Present.')->success()->send();
                    }),

                Action::make('markAbsent')
                    ->label('Absent')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->size('sm')
                    ->visible(fn (StaffAttendanceRecord $record) => $record->status !== StaffAttendanceStatus::Absent)
                    ->action(function (StaffAttendanceRecord $record) {
                        $record->update(['status' => StaffAttendanceStatus::Absent]);
                        Notification::make()->title('Marked as Absent.')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('bulkMarkPresent')
                        ->label('Mark Present')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status' => StaffAttendanceStatus::Present]);
                            Notification::make()->title('Marked as Present.')->success()->send();
                        }),

                    Action::make('bulkMarkAbsent')
                        ->label('Mark Absent')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['status' => StaffAttendanceStatus::Absent]);
                            Notification::make()->title('Marked as Absent.')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaffAttendance::route('/'),
            'create' => Pages\CreateStaffAttendance::route('/create'),
            'edit'   => Pages\EditStaffAttendance::route('/{record}/edit'),
        ];
    }
}
