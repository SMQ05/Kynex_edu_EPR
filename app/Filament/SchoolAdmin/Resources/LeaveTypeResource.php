<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\LeaveApplicableTo;
use App\Filament\SchoolAdmin\Resources\LeaveTypeResource\Pages;
use App\Models\Tenant\LeaveType;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveTypeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_leave_requests';

    protected static ?string $model = LeaveType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Leave Types';

    protected static ?int $navigationSort = 7;

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Leave Type Details')
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Annual Leave, Sick Leave'),

                        Select::make('applicable_to')
                            ->options(LeaveApplicableTo::class)
                            ->required()
                            ,

                        TextInput::make('max_days_per_year')
                            ->label('Max Days Per Year')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('days'),

                        Toggle::make('is_paid')
                            ->label('Paid Leave')
                            ->default(true)
                            ->helperText('Does this leave type include salary?'),
                    ]),
            ]);
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('applicable_to')
                    ->badge()
                    ->sortable(),

                TextColumn::make('max_days_per_year')
                    ->label('Max Days/Year')
                    ->suffix(' days')
                    ->sortable(),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->label('Paid')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('leave_requests_count')
                    ->counts('leaveRequests')
                    ->label('Total Requests')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('applicable_to')
                    ->options(LeaveApplicableTo::class),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit'   => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
