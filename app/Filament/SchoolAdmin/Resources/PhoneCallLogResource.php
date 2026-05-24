<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\PhoneCallLogResource\Pages;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\PhoneCallLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhoneCallLogResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_phone_call_log';

    protected static ?string $model = PhoneCallLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone';

    protected static string|\UnitEnum|null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Phone Call Log';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Call')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Caller / Contact')->required()->maxLength(255),
                    TextInput::make('phone')->tel()->required()->maxLength(30),
                    Select::make('call_type')->options(PhoneCallLog::CALL_TYPES)->default('incoming')->native(false),
                    Select::make('status')->options(PhoneCallLog::STATUSES)->default('completed')->native(false),
                    DatePicker::make('call_date')->default(now())->required(),
                    TimePicker::make('call_time')->seconds(false),
                    TextInput::make('duration_minutes')->numeric()->minValue(0)->suffix('min'),
                    TextInput::make('purpose')->maxLength(255),
                    DatePicker::make('follow_up_date'),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                    Textarea::make('note')
                        ->rows(2)
                        ->columnSpanFull()
                        ->hintActions([
                            AiActions::refineInto('note', ['feature' => 'call_note_refine']),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('call_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PhoneCallLog::CALL_TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'incoming' ? 'info' : 'success'),
                TextColumn::make('call_date')->date('d M Y')->sortable(),
                TextColumn::make('purpose')->limit(30)->toggleable(),
                TextColumn::make('follow_up_date')->date('d M Y')->placeholder('—')
                    ->color(fn (?\Illuminate\Support\Carbon $state): string => $state && $state->isPast() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PhoneCallLog::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success', 'follow_up' => 'warning', 'pending' => 'gray', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('call_type')->options(PhoneCallLog::CALL_TYPES),
                SelectFilter::make('status')->options(PhoneCallLog::STATUSES),
                Filter::make('due_follow_up')
                    ->label('Follow-up due')
                    ->query(fn ($query) => $query->dueForFollowUp()),
            ])
            ->defaultSort('call_date', 'desc')
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
            'index'  => Pages\ListPhoneCallLogs::route('/'),
            'create' => Pages\CreatePhoneCallLog::route('/create'),
            'edit'   => Pages\EditPhoneCallLog::route('/{record}/edit'),
        ];
    }
}
