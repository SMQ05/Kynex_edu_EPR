<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\EmploymentType;
use App\Models\Tenant\Department;
use App\Models\Tenant\Designation;
use App\Models\Tenant\StaffProfile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;

class StaffResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_staff';

    protected static ?string $model = StaffProfile::class;

    protected static ?string $modelLabel = 'Staff Member';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?string $slug = 'staff';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?int $navigationSort = 1;

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employment Details')
                    ->icon('heroicon-o-briefcase')
                    ->columns(3)
                    ->schema([
                        Select::make('school_user_id')
                            ->label('User Account')
                            ->relationship('schoolUser', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),

                        TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. EMP-001'),

                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('designation_id', null)),

                        Select::make('designation_id')
                            ->relationship('designation', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get) {
                                $deptId = $get('department_id');

                                return $deptId
                                    ? Designation::where('department_id', $deptId)->pluck('name', 'id')
                                    : Designation::pluck('name', 'id');
                            }),

                        Select::make('campus_id')
                            ->relationship('campus', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('employment_type')
                            ->options(EmploymentType::class)
                            ->required()
                            ->native(false),

                        DatePicker::make('joining_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y'),

                        TextInput::make('qualification')
                            ->maxLength(255)
                            ->placeholder('e.g. M.Sc. Computer Science'),

                        TextInput::make('experience_years')
                            ->label('Experience (Years)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->suffix('yrs'),

                        TextInput::make('basic_salary_paisas')
                            ->label('Basic Salary')
                            ->numeric()
                            ->prefix('PKR')
                            ->required()
                            ->minValue(0)
                            ->helperText('Enter amount in PKR (e.g. 35000)'),
                    ]),

                Section::make('Emergency & Contact')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('emergency_contact_name')
                            ->label('Emergency Contact Name')
                            ->maxLength(255),

                        TextInput::make('emergency_contact_phone')
                            ->label('Emergency Contact Phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('personal_whatsapp')
                            ->label('Personal WhatsApp')
                            ->helperText('Used for teacher notifications (admin only)')
                            ->tel()
                            ->maxLength(15)
                            ->visible(fn () => auth()->user()
                                ?->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'TEACHER'])),
                    ]),

                Section::make('Bank Details')
                    ->icon('heroicon-o-building-library')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('bank_name')
                            ->maxLength(255),

                        TextInput::make('bank_account')
                            ->label('Account Number')
                            ->maxLength(100),
                    ]),
            ]);
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('schoolUser.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('designation.name')
                    ->label('Designation')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('campus.name')
                    ->label('Campus')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('employment_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('basic_salary_paisas')
                    ->label('Basic Salary')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 0))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('joining_date')
                    ->label('Joined')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('employee_id', 'desc')
            ->filters([
                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->preload(),

                SelectFilter::make('designation_id')
                    ->relationship('designation', 'name')
                    ->label('Designation')
                    ->preload(),

                SelectFilter::make('campus_id')
                    ->relationship('campus', 'name')
                    ->label('Campus')
                    ->preload(),

                SelectFilter::make('employment_type')
                    ->options(EmploymentType::class)
                    ->label('Employment Type'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            'index'  => StaffResource\Pages\ListStaff::route('/'),
            'create' => StaffResource\Pages\CreateStaff::route('/create'),
            'edit'   => StaffResource\Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
