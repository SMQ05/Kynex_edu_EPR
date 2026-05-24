<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ComplaintResource\Pages;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\Complaint;
use App\Models\Tenant\FrontOfficeReference;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiClassifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ComplaintResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_complaints';

    protected static ?string $model = Complaint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Complaints';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Complainant')
                ->columns(3)
                ->schema([
                    TextInput::make('complainant_name')->required()->maxLength(255),
                    TextInput::make('contact')->tel()->maxLength(30),
                    TextInput::make('email')->email()->maxLength(255),
                ]),

            Section::make('Complaint')
                ->columns(2)
                ->schema([
                    Select::make('complaint_type_id')
                        ->label('Type')
                        ->options(fn (): array => FrontOfficeReference::options('complaint_type'))
                        ->searchable()
                        ->native(false),
                    Select::make('source_id')
                        ->label('Source')
                        ->options(fn (): array => FrontOfficeReference::options('source'))
                        ->searchable()
                        ->native(false),
                    DatePicker::make('complaint_date')->default(now())->required(),
                    Select::make('status')->options(Complaint::STATUSES)->default('open')->native(false),
                    Textarea::make('description')->required()->rows(4)->columnSpanFull(),
                ]),

            Section::make('Handling')
                ->columns(2)
                ->schema([
                    Select::make('assigned_to')
                        ->label('Assigned to')
                        ->relationship('assignee', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('urgency')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->native(false)
                        ->nullable(),
                    Textarea::make('action_taken')
                        ->rows(4)
                        ->columnSpanFull()
                        ->hintActions([
                            AiActions::draftInto('action_taken', [
                                'instruction'   => 'a polite, empathetic response that acknowledges the complaint and outlines the next steps the school will take',
                                'contextFields' => ['description' => 'Complaint', 'complainant_name' => 'Complainant'],
                                'feature'       => 'complaint_response_draft',
                                'channel'       => 'email',
                            ]),
                        ]),
                    FileUpload::make('attachment_path')
                        ->label('Attachment')
                        ->directory('complaint-attachments')
                        ->nullable(),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('complainant_name')->searchable()->sortable(),
                TextColumn::make('complaintType.name')->label('Type')->badge()->toggleable(),
                TextColumn::make('complaint_date')->date('d M Y')->sortable(),
                TextColumn::make('urgency')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'high' => 'danger', 'medium' => 'warning', 'low' => 'gray', default => 'gray',
                    }),
                TextColumn::make('assignee.name')->label('Assigned')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'gray', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(Complaint::STATUSES),
                SelectFilter::make('urgency')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),
            ])
            ->defaultSort('complaint_date', 'desc')
            ->actions([
                Action::make('aiTriage')
                    ->label('AI Triage')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->requiresConfirmation()
                    ->modalDescription('AI will suggest a category, urgency and sentiment from the complaint text. You can still edit afterwards.')
                    ->action(fn (Complaint $record) => static::runTriage($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /** Use the AI classifier to fill category, urgency and sentiment. */
    protected static function runTriage(Complaint $record): void
    {
        try {
            $classifier = app(AiClassifier::class);
            $text = (string) $record->description;

            $update = [];

            $urgency = $classifier->score($text, 'urgency of this school complaint (how quickly it needs attention)', 'complaint_urgency');
            $update['urgency'] = $urgency['band'];

            $sentiment = $classifier->sentiment($text, 'complaint_sentiment');
            $update['sentiment'] = $sentiment['sentiment'];

            $typeOptions = FrontOfficeReference::options('complaint_type'); // id => name
            if ($typeOptions !== []) {
                $labels = array_values($typeOptions);
                $pick = $classifier->classify($text, $labels, 'complaint_category', 'complaint category');
                $matchedId = array_search($pick['label'], $typeOptions, true);
                if ($matchedId !== false) {
                    $update['complaint_type_id'] = $matchedId;
                }
            }

            $record->update($update);

            Notification::make()
                ->title('AI triage applied')
                ->body('Urgency: ' . ($update['urgency'] ?? '—') . ', sentiment: ' . ($update['sentiment'] ?? '—') . '. Review and adjust as needed.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI triage failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'edit'   => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
