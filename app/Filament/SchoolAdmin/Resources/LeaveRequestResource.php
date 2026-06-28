<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\LeaveStatus;
use App\Filament\SchoolAdmin\Resources\LeaveRequestResource\Pages;
use App\Models\Tenant\LeaveRequest;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_leave_requests';

    protected static ?string $model = LeaveRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return (string) LeaveRequest::where('status', LeaveStatus::Pending->value)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Leave Request')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Select::make('school_user_id')
                            ->relationship('schoolUser', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Staff Member'),

                        Select::make('leave_type_id')
                            ->relationship('leaveType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Leave Type'),

                        DatePicker::make('from_date')
                            ->required()
                            
                            ->displayFormat('d M Y')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcDays($get, $set))
                            ->rules([
                                fn (Get $get, ?LeaveRequest $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $schoolUserId = $get('school_user_id');
                                    $toDate = $get('to_date');
                                    
                                    if (!$schoolUserId || !$value || !$toDate) {
                                        return;
                                    }
                                    
                                    $query = LeaveRequest::where('school_user_id', $schoolUserId)
                                        ->where('from_date', $value)
                                        ->where('to_date', $toDate);
                                        
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    
                                    if ($query->exists()) {
                                        $fail('A duplicate leave request for these exact dates already exists.');
                                    }
                                }
                            ]),

                        DatePicker::make('to_date')
                            ->required()

                            ->displayFormat('d M Y')
                            ->afterOrEqual('from_date')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcDays($get, $set)),

                        TextInput::make('total_days')
                            ->numeric()
                            ->required()
                            ->minValue(0.5)
                            ->readOnly()
                            ->suffix('days')
                            ->helperText('Auto-calculated from selected dates'),

                        Select::make('status')
                            ->options(LeaveStatus::class)
                            ->default(LeaveStatus::Pending->value)
                            ->required()
                            ,

                        Textarea::make('reason')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Textarea::make('review_note')
                            ->rows(2)
                            ->maxLength(500)
                            ->label('Review Note (optional)')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    private static function recalcDays(Get $get, Set $set): void
    {
        $from = $get('from_date');
        $to   = $get('to_date');
        if ($from && $to) {
            $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
            $set('total_days', max(0.5, $days));
        }
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

                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('from_date')
                    ->label('From')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('to_date')
                    ->label('To')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Days')
                    ->suffix(' days')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(LeaveStatus::class),

                SelectFilter::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->label('Leave Type'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Leave Request')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Note (optional)')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status'      => LeaveStatus::Approved,
                            'reviewed_by' => auth()->guard('school_users')->id(),
                            'reviewed_at' => now(),
                            'review_note' => $data['review_note'] ?? null,
                        ]);
                        Notification::make()
                            ->title('Leave approved for ' . $record->schoolUser?->name . '.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Reject Leave Request')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status'      => LeaveStatus::Rejected,
                            'reviewed_by' => auth()->guard('school_users')->id(),
                            'reviewed_at' => now(),
                            'review_note' => $data['review_note'],
                        ]);
                        Notification::make()
                            ->title('Leave request rejected.')
                            ->warning()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === LeaveStatus::Pending) {
                                    $record->update([
                                        'status'      => LeaveStatus::Approved,
                                        'reviewed_by' => auth()->guard('school_users')->id(),
                                        'reviewed_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} leave requests approved.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
