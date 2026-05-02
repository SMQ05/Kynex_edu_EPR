<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\NotificationTemplateResource\Pages;
use App\Models\Tenant\NotificationTemplate;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Str;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class NotificationTemplateResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'send_notifications_all';

    protected static ?string $model = NotificationTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Notification Templates';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Template Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('event_trigger')
                    ->label('Event Trigger')
                    ->options([
                        'student.absent' => 'Student Absent',
                        'fee.overdue' => 'Fee Overdue',
                        'fee.payment_received' => 'Fee Payment Received',
                        'exam.result_published' => 'Exam Result Published',
                        'leave.approved' => 'Leave Approved',
                        'leave.rejected' => 'Leave Rejected',
                        'admission.confirmed' => 'Admission Confirmed',
                        'monthly_billing' => 'Monthly Billing',
                        'gate_pass.approved' => 'Gate Pass Approved',
                        'payroll.ready' => 'Payroll Ready',
                        'manual' => 'Manual / Custom',
                    ])
                    ->searchable(),

                CheckboxList::make('send_to')
                    ->label('Send To')
                    ->options([
                        'student' => 'Student',
                        'parent' => 'Parent',
                        'teacher' => 'Teacher',
                        'admin' => 'Admin',
                    ])
                    ->columns(4),

                CheckboxList::make('channels')
                    ->label('Channels')
                    ->options([
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        'in_app' => 'In-App',
                    ])
                    ->columns(4),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),

            Section::make('SMS Template')->schema([
                Textarea::make('sms_template')
                    ->rows(3)
                    ->helperText('Variables: {student_name}, {parent_name}, {class}, {date}, {fee_amount}, {school_name}, {due_date}, etc.')
                    ->columnSpanFull(),
            ])->collapsible(),

            Section::make('WhatsApp Template')->schema([
                Textarea::make('whatsapp_template')
                    ->rows(3)
                    ->helperText('Variables: {student_name}, {parent_name}, {class}, {date}, {fee_amount}, {school_name}, etc.')
                    ->columnSpanFull(),
            ])->collapsible(),

            Section::make('Email Template')->schema([
                TextInput::make('email_subject')
                    ->label('Email Subject')
                    ->helperText('Variables: {student_name}, {school_name}, etc.'),

                Textarea::make('email_body')
                    ->label('Email Body')
                    ->rows(5)
                    ->columnSpanFull(),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_trigger')
                    ->label('Trigger')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('channels')
                    ->label('Channels')
                    ->badge()
                    ->separator(',')
                    ->color('success'),

                TextColumn::make('send_to')
                    ->label('Recipients')
                    ->badge()
                    ->separator(',')
                    ->color('warning'),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
