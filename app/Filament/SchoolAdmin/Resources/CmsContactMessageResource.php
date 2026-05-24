<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\CmsContactMessageResource\Pages;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Models\Tenant\CmsContactMessage;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiDraftService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
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

class CmsContactMessageResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsContactMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static ?int $navigationSort = 22;

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::query()->where('status', 'new')->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Message')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->tel()->maxLength(30),
                    TextInput::make('subject')->maxLength(255),
                    Textarea::make('message')->required()->rows(4)->columnSpanFull()->disabled(),
                    Select::make('status')
                        ->options(CmsContactMessage::STATUSES)
                        ->default('new')
                        ->native(false),
                ]),

            Section::make('Reply')
                ->schema([
                    Textarea::make('reply')
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('Draft a reply to send back to the sender.')
                        ->hintActions([
                            AiActions::draftInto('reply', [
                                'instruction'   => 'a polite, helpful reply to this website contact-form message that answers their question and invites further contact',
                                'contextFields' => ['name' => 'Sender', 'subject' => 'Subject', 'message' => 'Their message'],
                                'feature'       => 'cms_contact_reply_draft',
                                'channel'       => 'email',
                            ]),
                            AiActions::refineInto('reply', ['feature' => 'cms_contact_reply_refine']),
                        ]),
                    Placeholder::make('replied_at')
                        ->label('Replied at')
                        ->content(fn (?CmsContactMessage $record): string => $record?->replied_at?->format('d M Y H:i') ?? 'Not yet replied'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('subject')->limit(40)->placeholder('—')->toggleable(),
                TextColumn::make('message')->limit(50)->wrap(),
                TextColumn::make('email')->toggleable()->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger', 'read' => 'warning', 'replied' => 'success', 'archived' => 'gray', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => CmsContactMessage::STATUSES[$state] ?? $state),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CmsContactMessage::STATUSES),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('aiReply')
                    ->label('AI Reply')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->requiresConfirmation()
                    ->modalDescription('AI will draft a reply from the message. Review and edit before sending.')
                    ->action(fn (CmsContactMessage $record) => static::draftReply($record)),
                Action::make('markReplied')
                    ->label('Mark replied')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CmsContactMessage $record): bool => $record->status !== 'replied' && filled($record->reply))
                    ->action(function (CmsContactMessage $record): void {
                        $record->update([
                            'status'     => 'replied',
                            'replied_at' => now(),
                            'replied_by' => auth('school_users')->id(),
                        ]);
                        Notification::make()->title('Marked as replied')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /** Draft a reply with AI and store it on the record (advisory). */
    protected static function draftReply(CmsContactMessage $record): void
    {
        try {
            $reply = app(AiDraftService::class)->draft(
                instruction: 'a polite, helpful reply to this website contact-form message that answers their question and invites further contact',
                context: [
                    'Sender'       => (string) $record->name,
                    'Subject'      => (string) $record->subject,
                    'Their message' => (string) $record->message,
                ],
                feature: 'cms_contact_reply_draft',
                options: ['channel' => 'email'],
            );

            $record->update([
                'reply'  => $reply,
                'status' => $record->status === 'new' ? 'read' : $record->status,
            ]);

            Notification::make()
                ->title('Reply drafted')
                ->body('Open the message to review, edit and send.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI reply failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsContactMessages::route('/'),
            'create' => Pages\CreateCmsContactMessage::route('/create'),
            'edit'   => Pages\EditCmsContactMessage::route('/{record}/edit'),
        ];
    }
}
