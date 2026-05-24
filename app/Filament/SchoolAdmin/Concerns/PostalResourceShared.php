<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Concerns;

use App\Models\Tenant\FrontOfficeReference;
use App\Models\Tenant\PostalRecord;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiExtractor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Shared form/table for the Postal Receive and Postal Dispatch resources,
 * which sit over a single `postal_records` table discriminated by
 * `direction`. Keeps both resources DRY (no duplicated CRUD).
 */
trait PostalResourceShared
{
    protected static function postalForm(Schema $schema, string $direction): Schema
    {
        $isReceive = $direction === PostalRecord::DIRECTION_RECEIVE;

        return $schema->schema([
            Section::make($isReceive ? 'Incoming Mail' : 'Outgoing Mail')
                ->columns(2)
                ->schema([
                    Hidden::make('direction')->default($direction),
                    TextInput::make('reference_no')->maxLength(255),
                    DatePicker::make('record_date')->default(now())->required(),
                    TextInput::make('from_party')
                        ->label($isReceive ? 'From (sender)' : 'From (department/person)')
                        ->maxLength(255),
                    TextInput::make('to_party')
                        ->label($isReceive ? 'To (recipient)' : 'To (addressee)')
                        ->maxLength(255),
                    TextInput::make('title')->label('Subject')->required()->maxLength(255),
                    Select::make('postal_type_id')
                        ->label('Type')
                        ->options(fn (): array => FrontOfficeReference::options('postal_type'))
                        ->searchable()
                        ->native(false),
                    Toggle::make('is_confidential')->default(false),
                    Textarea::make('details')->rows(3)->columnSpanFull(),
                    FileUpload::make('attachment_path')
                        ->label('Scan / Attachment')
                        ->directory('postal-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->nullable()
                        ->helperText('Save the record, then use "AI Extract" to auto-read sender, reference and subject from the scan.'),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    protected static function postalTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('record_date')->date('d M Y')->sortable(),
                TextColumn::make('reference_no')->searchable()->placeholder('—'),
                TextColumn::make('title')->label('Subject')->searchable()->limit(40),
                TextColumn::make('from_party')->placeholder('—')->toggleable(),
                TextColumn::make('to_party')->placeholder('—')->toggleable(),
                TextColumn::make('postalType.name')->label('Type')->badge()->placeholder('—'),
                IconColumn::make('is_confidential')->boolean()->label('Conf.'),
            ])
            ->defaultSort('record_date', 'desc')
            ->actions([
                Action::make('aiExtract')
                    ->label('AI Extract')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (PostalRecord $record): bool => AiAvailability::enabled() && filled($record->attachment_path))
                    ->requiresConfirmation()
                    ->modalDescription('AI reads the attached scan and fills sender, reference and subject where blank. Review afterwards.')
                    ->action(fn (PostalRecord $record) => static::runPostalExtract($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    protected static function runPostalExtract(PostalRecord $record): void
    {
        try {
            $disk = config('filesystems.default', 'local');
            if (! Storage::disk($disk)->exists($record->attachment_path)) {
                throw new \RuntimeException('Attachment file not found.');
            }
            $absolute = Storage::disk($disk)->path($record->attachment_path);

            $data = app(AiExtractor::class)->extractFromFile($absolute, [
                'reference_no' => 'reference / tracking number',
                'from_party'   => 'sender name or organisation',
                'to_party'     => 'recipient / addressee',
                'title'        => 'subject or short summary of the letter',
            ], 'postal_extract');

            // Only fill blanks — never overwrite what a human typed.
            $update = [];
            foreach (['reference_no', 'from_party', 'to_party'] as $field) {
                if (blank($record->{$field}) && filled($data[$field] ?? null)) {
                    $update[$field] = $data[$field];
                }
            }
            if (filled($data['title'] ?? null) && (blank($record->title) || $record->title === 'Untitled')) {
                $update['title'] = $data['title'];
            }

            if ($update !== []) {
                $record->update($update);
            }

            Notification::make()
                ->title($update === [] ? 'Nothing new to extract' : 'Details extracted')
                ->body($update === [] ? 'Fields already filled.' : 'Review the extracted values.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI extract failed')->body($e->getMessage())->danger()->send();
        }
    }
}
