<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\ApplicationStatus;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\StudentApplication;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StudentApplicationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_admissions';

    protected static ?string $model = StudentApplication::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Admissions';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Applicant')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('first_name')->required(),
                    Components\TextInput::make('last_name')->required(),
                    Components\DatePicker::make('date_of_birth'),
                    Components\Select::make('gender')->options([
                        'male' => 'Male', 'female' => 'Female', 'other' => 'Other',
                    ]),
                    Components\TextInput::make('phone')->tel(),
                    Components\TextInput::make('email')->email(),
                    Components\TextInput::make('address')->columnSpanFull(),
                    Components\TextInput::make('city'),
                    Components\TextInput::make('previous_school'),
                ]),

            Section::make('Guardian')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('father_name'),
                    Components\TextInput::make('mother_name'),
                    Components\TextInput::make('guardian_phone')->tel(),
                    Components\TextInput::make('guardian_email')->email(),
                ]),

            Section::make('Class & Campus')
                ->columns(3)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->options(fn () => AcademicYear::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('campus_id')
                        ->options(fn () => Campus::pluck('name', 'id'))
                        ->searchable(),
                ]),

            Section::make('Entry Test')
                ->columns(2)
                ->schema([
                    Components\DateTimePicker::make('entry_test_scheduled_at'),
                    Components\TextInput::make('entry_test_room'),
                    Components\TextInput::make('entry_test_score')->numeric(),
                    Components\Textarea::make('entry_test_notes')->columnSpanFull()->rows(2),
                ]),

            Section::make('Decision')
                ->columns(1)
                ->schema([
                    Components\Select::make('status')
                        ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required(),
                    Components\Textarea::make('decision_notes')->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Name')
                    ->formatStateUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('campus.name')->label('Campus')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('guardian_phone')->label('Guardian phone')->toggleable(),
                Tables\Columns\TextColumn::make('entry_test_score')->label('Test')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ApplicationStatus ? $state->label() : $state)
                    ->color(fn ($state): string => $state instanceof ApplicationStatus ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                Tables\Filters\SelectFilter::make('campus_id')->relationship('campus', 'name')->label('Campus'),
            ])
            ->actions([
                EditAction::make(),

                Action::make('admit')
                    ->label('Admit')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (StudentApplication $r) => $r->status !== ApplicationStatus::Admitted)
                    ->requiresConfirmation()
                    ->action(function (StudentApplication $r) {
                        app(\App\Services\StudentApplicationService::class)->admit($r);
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StudentApplication $r) => $r->status !== ApplicationStatus::Rejected)
                    ->form([
                        Components\Textarea::make('decision_notes')->required()->rows(3),
                    ])
                    ->action(function (StudentApplication $r, array $data) {
                        $r->update([
                            'status'         => ApplicationStatus::Rejected,
                            'decision_notes' => $data['decision_notes'],
                            'reviewed_at'    => now(),
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentApplications::route('/'),
            'create' => Pages\CreateStudentApplication::route('/create'),
            'edit'   => Pages\EditStudentApplication::route('/{record}/edit'),
        ];
    }
}
