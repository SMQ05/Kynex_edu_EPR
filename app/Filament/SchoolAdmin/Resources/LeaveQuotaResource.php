<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\LeaveQuotaResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\LeaveQuota;
use App\Models\Tenant\LeaveType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

/**
 * Leave Define (Infix "Leave → Leave Define"): per-role leave QUOTAS against
 * the existing LeaveType list, so leave balances are defined.
 */
class LeaveQuotaResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_leave_requests';

    protected static ?string $model = LeaveQuota::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Leave Define';

    protected static ?int $navigationSort = 8;

    /** @return array<string,string> */
    protected static function roleOptions(): array
    {
        try {
            return Role::query()
                ->where('guard_name', 'school_users')
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Leave Quota')
                ->columns(2)
                ->schema([
                    Select::make('leave_type_id')
                        ->label('Leave type')
                        ->options(fn (): array => LeaveType::orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->required(),
                    Select::make('applies_to_role')
                        ->label('Applies to role')
                        ->options(fn (): array => static::roleOptions())
                        ->searchable()->nullable()
                        ->helperText('Leave blank to apply to everyone.'),
                    TextInput::make('days_allowed')
                        ->label('Days allowed')
                        ->numeric()->required()->minValue(0)->maxValue(366),
                    Select::make('period')
                        ->options(LeaveQuota::PERIODS)
                        ->default('yearly')->required()->native(false),
                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn (): array => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all())
                        ->searchable()->nullable(),
                    Toggle::make('carry_forward')->label('Allow carry forward')->live(),
                    TextInput::make('max_carry_forward_days')
                        ->label('Max carry-forward days')
                        ->numeric()->minValue(0)
                        ->visible(fn (Get $get): bool => (bool) $get('carry_forward')),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('leaveType.name')->label('Leave type')->searchable()->weight('semibold'),
                TextColumn::make('applies_to_role')->label('Role')->badge()->placeholder('Everyone'),
                TextColumn::make('days_allowed')->label('Days')->sortable()->badge(),
                TextColumn::make('period')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LeaveQuota::PERIODS[$state] ?? $state),
                IconColumn::make('carry_forward')->boolean()->label('Carry')->toggleable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                SelectFilter::make('leave_type_id')->relationship('leaveType', 'name')->label('Leave type'),
                SelectFilter::make('applies_to_role')->options(fn (): array => static::roleOptions())->label('Role'),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index'  => Pages\ListLeaveQuotas::route('/'),
            'create' => Pages\CreateLeaveQuota::route('/create'),
            'edit'   => Pages\EditLeaveQuota::route('/{record}/edit'),
        ];
    }
}
