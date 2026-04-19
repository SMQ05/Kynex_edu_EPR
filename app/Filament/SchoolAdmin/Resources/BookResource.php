<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\Book;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class BookResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_books';

    protected static ?string $model = Book::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static string | \UnitEnum | null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Book Information')->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                TextInput::make('author')
                    ->maxLength(255),

                TextInput::make('isbn')
                    ->label('ISBN')
                    ->maxLength(20),

                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        Textarea::make('description'),
                    ]),

                TextInput::make('publisher')
                    ->maxLength(255),

                TextInput::make('edition_year')
                    ->label('Edition Year')
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(2030),

                TextInput::make('rack_number')
                    ->maxLength(50),

                TextInput::make('total_copies')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1),

                TextInput::make('available_copies')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0),

                TextInput::make('price_paisas')
                    ->label('Price (Paisas)')
                    ->numeric()
                    ->default(0)
                    ->helperText('Enter price in paisas (100 paisas = 1 PKR)'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query, Table $table) {
                $search = $table->getSearchQuery();
                if (filled($search)) {
                    $query->search($search);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('author')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rack_number')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_copies')
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_copies')
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),

                Tables\Filters\TernaryFilter::make('is_active'),

                Tables\Filters\Filter::make('available')
                    ->query(fn ($query) => $query->where('available_copies', '>', 0))
                    ->label('Available Only'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index'  => BookResource\Pages\ListBooks::route('/'),
            'create' => BookResource\Pages\CreateBook::route('/create'),
            'edit'   => BookResource\Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
