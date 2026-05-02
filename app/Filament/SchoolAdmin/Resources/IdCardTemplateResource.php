<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\IdCardTemplateResource\Pages;
use App\Models\Tenant\IdCardTemplate;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IdCardTemplateResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = IdCardTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & ID Cards';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ID Card Template')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('card_type')
                            ->options([
                                'student' => 'Student',
                                'staff'   => 'Staff',
                            ])
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),

                Section::make('HTML Template')
                    ->description('Design the ID card front using HTML/CSS. Use {{variable}} placeholders.')
                    ->schema([
                        Textarea::make('html_template')
                            ->required()
                            ->rows(20)
                            ->columnSpanFull()
                            ->helperText('Student: {{student_name}}, {{admission_number}}, {{class_name}}, {{section_name}}, {{photo_url}}, {{barcode}}, {{blood_group}}, {{father_name}}, {{address}} | Staff: {{full_name}}, {{employee_id}}, {{department}}, {{designation}}, {{photo_url}}'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('card_type')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('card_type')
                    ->options([
                        'student' => 'Student',
                        'staff'   => 'Staff',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Clone')
                    ->excludeAttributes(['id'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['name']      = ($data['name'] ?? 'Template') . ' (Copy)';
                        $data['is_active'] = false;
                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdCardTemplates::route('/'),
            'create' => Pages\CreateIdCardTemplate::route('/create'),
            'edit' => Pages\EditIdCardTemplate::route('/{record}/edit'),
        ];
    }
}
