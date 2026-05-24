<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\Pages;
use App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\RelationManagers\FollowupsRelationManager;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\AdmissionEnquiry;
use App\Models\Tenant\FrontOfficeReference;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiClassifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdmissionEnquiryResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inquiries';

    protected static ?string $model = AdmissionEnquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Admission Queries';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Enquiry')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('phone')->tel()->maxLength(30),
                    TextInput::make('email')->email()->maxLength(255),
                    Select::make('interested_class_id')
                        ->label('Interested class')
                        ->relationship('interestedClass', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('source_id')
                        ->label('Source')
                        ->options(fn (): array => FrontOfficeReference::options('source'))
                        ->searchable()
                        ->native(false),
                    TextInput::make('number_of_children')->numeric()->minValue(1)->nullable(),
                    Textarea::make('address')->rows(2)->columnSpanFull(),
                    Textarea::make('description')->label('Requirement / notes')->rows(3)->columnSpanFull(),
                ]),

            Section::make('Tracking')
                ->columns(2)
                ->schema([
                    Select::make('assigned_to')
                        ->label('Assigned to')
                        ->relationship('assignee', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('status')->options(AdmissionEnquiry::STATUSES)->default('active')->native(false),
                    DatePicker::make('enquiry_date')->default(now()),
                    DatePicker::make('next_follow_up_date'),
                    Textarea::make('note')
                        ->rows(3)
                        ->columnSpanFull()
                        ->hintActions([
                            AiActions::draftInto('note', [
                                'instruction'   => 'a brief, friendly follow-up message inviting the parent to visit/apply, tailored to their enquiry',
                                'contextFields' => ['name' => 'Parent', 'description' => 'Enquiry', 'interested_class_id' => 'Interested class id'],
                                'feature'       => 'enquiry_followup_draft',
                                'channel'       => 'whatsapp',
                            ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->toggleable(),
                TextColumn::make('interestedClass.name')->label('Class')->toggleable(),
                TextColumn::make('lead_score')
                    ->label('Lead')
                    ->badge()
                    ->formatStateUsing(fn (?int $state, AdmissionEnquiry $r): string => $state === null ? '—' : $state . ' (' . ($r->lead_band ?? '') . ')')
                    ->color(fn (AdmissionEnquiry $r): string => match ($r->lead_band) {
                        'high' => 'success', 'medium' => 'warning', 'low' => 'gray', default => 'gray',
                    }),
                TextColumn::make('next_follow_up_date')->date('d M Y')->sortable()
                    ->color(fn (?\Illuminate\Support\Carbon $state): string => $state && $state->isPast() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'won' => 'success', 'active' => 'info', 'passive' => 'warning', 'lost', 'dead' => 'gray', default => 'gray',
                    }),
                TextColumn::make('assignee.name')->label('Owner')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(AdmissionEnquiry::STATUSES),
                Filter::make('due_follow_up')
                    ->label('Follow-up due')
                    ->query(fn ($query) => $query->dueForFollowUp()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('aiScore')
                    ->label('AI Lead Score')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->action(fn (AdmissionEnquiry $record) => static::runLeadScore($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    protected static function runLeadScore(AdmissionEnquiry $record): void
    {
        try {
            $context = sprintf(
                "Enquiry from %s. Interested class: %s. Children: %s. Notes: %s",
                $record->name,
                $record->interestedClass?->name ?? 'unspecified',
                $record->number_of_children ?? 'unspecified',
                $record->description ?? '—',
            );

            $result = app(AiClassifier::class)->score(
                $context,
                'likelihood this enquiry will convert into an actual enrolment (lead quality)',
                'enquiry_lead_score',
            );

            $record->update(['lead_score' => $result['score'], 'lead_band' => $result['band']]);

            Notification::make()
                ->title('Lead scored: ' . $result['score'] . '/100 (' . $result['band'] . ')')
                ->body($result['reason'] ?: null)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI lead score failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getRelations(): array
    {
        return [FollowupsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdmissionEnquiries::route('/'),
            'create' => Pages\CreateAdmissionEnquiry::route('/create'),
            'edit'   => Pages\EditAdmissionEnquiry::route('/{record}/edit'),
        ];
    }
}
