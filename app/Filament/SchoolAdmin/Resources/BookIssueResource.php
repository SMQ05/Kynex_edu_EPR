<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\BookIssueStatus;
use App\Models\Tenant\BookIssue;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class BookIssueResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'issue_books';

    protected static ?string $model = BookIssue::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string | \UnitEnum | null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Issue / Return';

    protected static ?string $modelLabel = 'Book Issue';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Issue Details')->schema([
                Select::make('book_id')
                    ->relationship('book', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('library_member_id')
                    ->relationship('member', 'library_card_number')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('issue_date')
                    ->required()
                    ->default(now()),

                DatePicker::make('due_date')
                    ->required()
                    ->default(now()->addDays(14)),

                DatePicker::make('return_date'),

                Select::make('status')
                    ->options(
                        collect(BookIssueStatus::cases())
                            ->mapWithKeys(fn (BookIssueStatus $s) => [$s->value => $s->label()])
                            ->all()
                    )
                    ->default(BookIssueStatus::Issued->value)
                    ->required(),

                TextInput::make('fine_paisas')
                    ->label('Fine (Paisas)')
                    ->numeric()
                    ->default(0),

                Toggle::make('fine_paid')
                    ->label('Fine Paid'),

                Textarea::make('remarks')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('book.title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('member.library_card_number')
                    ->label('Card #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('return_date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => BookIssueStatus::tryFrom($state)?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('fine_paisas')
                    ->label('Fine')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'PKR ' . number_format($state / 100, 2) : '—')
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\IconColumn::make('fine_paid')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(BookIssueStatus::cases())
                            ->mapWithKeys(fn (BookIssueStatus $s) => [$s->value => $s->label()])
                            ->all()
                    ),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('status', 'issued')->where('due_date', '<', now()))
                    ->label('Overdue Only'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => BookIssueResource\Pages\ListBookIssues::route('/'),
            'create' => BookIssueResource\Pages\CreateBookIssue::route('/create'),
            'edit'   => BookIssueResource\Pages\EditBookIssue::route('/{record}/edit'),
        ];
    }
}
