<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\NoticeResource\Pages;
use App\Models\Tenant\Notice;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class NoticeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_announcement_board';

    protected static ?string $model = Notice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Notice Details')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->required()
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'orderedList',
                            'bulletList',
                            'h2',
                            'h3',
                            'blockquote',
                        ]),

                    CheckboxList::make('target_roles')
                        ->label('Visible To')
                        ->options([
                            'SCHOOL_ADMIN'   => 'School Admin',
                            'TEACHER'        => 'Teachers',
                            'STUDENT'        => 'Students',
                            'PARENT'         => 'Parents',
                            'ACCOUNTANT'     => 'Accountant',
                            'LIBRARIAN'      => 'Librarian',
                            'RECEPTIONIST'   => 'Receptionist',
                        ])
                        ->required()
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Publishing')
                ->columns(3)
                ->schema([
                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true),

                    DateTimePicker::make('published_at')
                        ->label('Publish Date')
                        ->default(now())
                        ->native(false),

                    DateTimePicker::make('expires_at')
                        ->label('Expires At')
                        ->native(false)
                        ->helperText('Leave empty for no expiration'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('target_roles')
                    ->label('Audience')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SCHOOL_ADMIN' => 'Admin',
                        'TEACHER'      => 'Teacher',
                        'STUDENT'      => 'Student',
                        'PARENT'       => 'Parent',
                        default        => $state,
                    })
                    ->colors([
                        'primary'   => 'SCHOOL_ADMIN',
                        'success'   => 'TEACHER',
                        'info'      => 'STUDENT',
                        'warning'   => 'PARENT',
                    ]),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNotices::route('/'),
            'create' => Pages\CreateNotice::route('/create'),
            'edit'   => Pages\EditNotice::route('/{record}/edit'),
        ];
    }
}
