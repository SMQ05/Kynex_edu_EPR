<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\SubjectAttendanceResource\Pages;
use App\Models\Tenant\SubjectAttendanceRecord;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubjectAttendanceResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'mark_attendance_manual';

    protected static ?string $model = SubjectAttendanceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?string $navigationLabel = 'Subject Attendance';

    protected static ?int $navigationSort = 3;

    /** @return array<string,string> */
    protected static function statusOptions(): array
    {
        $out = [];
        foreach (AttendanceStatus::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Subject Attendance')
                ->columns(2)
                ->schema([
                    Select::make('class_id')
                        ->label('Class')
                        ->relationship('schoolClass', 'name')
                        ->searchable()->preload()->required()->live(),
                    Select::make('section_id')
                        ->label('Section')
                        ->relationship('section', 'name')
                        ->searchable()->preload()->required(),
                    Select::make('subject_id')
                        ->label('Subject')
                        ->relationship('subject', 'name')
                        ->searchable()->preload()->required(),
                    Select::make('student_id')
                        ->label('Student')
                        ->relationship('student', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name . ' ' . $record->last_name))
                        ->searchable(['first_name', 'last_name', 'admission_number'])
                        ->preload()
                        ->required(),
                    DatePicker::make('date')->default(now())->required(),
                    TextInput::make('period')->maxLength(30)->placeholder('e.g. 1, P3, Morning'),
                    Select::make('status')->options(static::statusOptions())->default('present')->required()->native(false),
                    Textarea::make('remarks')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->getStateUsing(fn ($record) => trim(($record->student->first_name ?? '') . ' ' . ($record->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('subject.name')->label('Subject')->toggleable(),
                TextColumn::make('schoolClass.name')->label('Class')->toggleable(),
                TextColumn::make('section.name')->label('Section')->toggleable(),
                TextColumn::make('period')->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => ($state instanceof AttendanceStatus ? $state : AttendanceStatus::tryFrom((string) $state))?->color() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('subject_id')->relationship('subject', 'name')->searchable()->preload(),
                SelectFilter::make('class_id')->relationship('schoolClass', 'name')->searchable()->preload(),
                SelectFilter::make('status')->options(static::statusOptions()),
            ])
            ->defaultSort('date', 'desc')
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
            'index'  => Pages\ListSubjectAttendanceRecords::route('/'),
            'create' => Pages\CreateSubjectAttendanceRecord::route('/create'),
            'edit'   => Pages\EditSubjectAttendanceRecord::route('/{record}/edit'),
        ];
    }
}
